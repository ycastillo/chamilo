<?php //$id:$
/**
 * This (abstract?) class defines the parent attributes and methods for the dokeos learnpaths and scorm
 * learnpaths. It is used by the scorm class as well as the dokeos_lp class.
 * @package dokeos.learnpath
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 * @license	GNU/GPL - See Dokeos license directory for details
 */
/**
 * Defines the learnpath parent class
 * @package dokeos.learnpath
 */
class learnpath {

	var $attempt = 0; //the number for the current ID view
	var $cc; //course (code) this learnpath is located in
	var $current; //id of the current item the user is viewing
	var $current_score; //the score of the current item
	var $current_time_start; //the time the user loaded this resource (this does not mean he can see it yet)
	var $current_time_stop; //the time the user closed this resource
	var $default_status = 'not attempted';
	var $encoding = 'ISO-8859-1';
	var $error = '';
	var $extra_information = ''; //this string can be used by proprietary SCORM contents to store data about the current learnpath
	var $force_commit = false; //for SCORM only - if set to true, will send a scorm LMSCommit() request on each LMSSetValue()
	var $index; //the index of the active learnpath_item in $ordered_items array
	var $items = array();
	var $last; //item_id of last item viewed in the learning path
	var $last_item_seen = 0; //in case we have already come in this learnpath, reuse the last item seen if authorized
	var $license; //which license this course has been given - not used yet on 20060522
	var $lp_id; //DB ID for this learnpath
	var $lp_view_id; //DB ID for lp_view
	var $log_file; //file where to log learnpath API msg
	var $maker; //which maker has conceived the content (ENI, Articulate, ...)
	var $message = '';
	var $mode='embedded'; //holds the video display mode (fullscreen or embedded) 
	var $name; //learnpath name (they generally have one)
	var $ordered_items = array(); //list of the learnpath items in the order they are to be read
	var $path = ''; //path inside the scorm directory (if scorm)
	
	// Tells if all the items of the learnpath can be tried again. Defaults to "no" (=1)
	var $prevent_reinit = 1;
	
	// Describes the mode of progress bar display
	var $progress_bar_mode = '%'; 
	
	// Percentage progress as saved in the db
	var $progress_db = '0'; 
	var $proximity; //wether the content is distant or local or unknown
	var $refs_list = array(); //list of items by ref => db_id. Used only for prerequisites match. 
	//!!!This array (refs_list) is built differently depending on the nature of the LP. 
	//If SCORM, uses ref, if Dokeos, uses id to keep a unique value
	var $type; //type of learnpath. Could be 'dokeos', 'scorm', 'scorm2004', 'aicc', ... 
	//TODO check if this type variable is useful here (instead of just in the controller script)
	var $user_id; //ID of the user that is viewing/using the course
	var $update_queue = array();
	var $scorm_debug = 0;
	
	var $arrMenu = array(); //array for the menu items

	var $debug = 0; //logging level



	/**
	 * Class constructor. Needs a database handler, a course code and a learnpath id from the database.
	 * Also builds the list of items into $this->items.
	 * @param	string		Course code
	 * @param	integer		Learnpath ID
	 * @param	integer		User ID
	 * @return	boolean		True on success, false on error
	 */
    function learnpath($course, $lp_id, $user_id) {
    	//check params
    	//check course code
    	if($this->debug>0){error_log('New LP - In learnpath::learnpath('.$course.','.$lp_id.','.$user_id.')',0);}
    	if(empty($course)){
    		$this->error = 'Course code is empty';
    		return false;
    	}
    	else
    	{
    		$main_table = Database::get_main_table(TABLE_MAIN_COURSE);
    		//$course = Database::escape_string($course);
    		$course = $this->escape_string($course);
    		$sql = "SELECT * FROM $main_table WHERE code = '$course'";
    		if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - Querying course: '.$sql,0);}
    		//$res = Database::query($sql);
    		$res = api_sql_query($sql);
    		if(Database::num_rows($res)>0)
    		{
    			$this->cc = $course;
    		}
    		else
    		{
    			$this->error = 'Course code does not exist in database ('.$sql.')';
    			return false;
	   		}
    	}
    	//check learnpath ID
    	if(empty($lp_id))
    	{
    		$this->error = 'Learnpath ID is empty';
    		return false;
    	}
    	else
    	{    		
    		//TODO make it flexible to use any course_code (still using env course code here)
	    	$lp_table = Database::get_course_table('lp');

    		//$id = Database::escape_integer($id);
    		$lp_id = $this->escape_string($lp_id);
    		$sql = "SELECT * FROM $lp_table WHERE id = '$lp_id'";
    		if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - Querying lp: '.$sql,0);}
    		//$res = Database::query($sql);
    		$res = api_sql_query($sql);
    		if(Database::num_rows($res)>0)
    		{
    			$this->lp_id = $lp_id;
    			$row = Database::fetch_array($res);
    			$this->type = $row['lp_type'];
    			$this->name = stripslashes($row['name']);
    			$this->encoding = $row['default_encoding'];
    			$this->proximity = $row['content_local'];
    			$this->maker = $row['content_maker'];
    			$this->prevent_reinit = $row['prevent_reinit'];
    			$this->license = $row['content_license'];
    			$this->scorm_debug = $row['debug'];
	   			$this->js_lib = $row['js_lib'];
	   			$this->path = $row['path'];
	   			if($this->type == 2){
    				if($row['force_commit'] == 1){
    					$this->force_commit = true;
    				}
    			}
    			$this->mode = $row['default_view_mod'];
    		}
    		else
    		{
    			$this->error = 'Learnpath ID does not exist in database ('.$sql.')';
    			return false;
    		}
    	}
    	//check user ID
    	if(empty($user_id)){
    		$this->error = 'User ID is empty';
    		return false;
    	}
    	else
    	{
    		//$main_table = Database::get_main_user_table();
    		$main_table = Database::get_main_table(TABLE_MAIN_USER);
    		//$user_id = Database::escape_integer($user_id);
    		$user_id = $this->escape_string($user_id);
    		$sql = "SELECT * FROM $main_table WHERE user_id = '$user_id'";
    		if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - Querying user: '.$sql,0);}
    		//$res = Database::query($sql);
    		$res = api_sql_query($sql);
    		if(Database::num_rows($res)>0)
    		{
    			$this->user_id = $user_id;
    		}
    		else
    		{
    			$this->error = 'User ID does not exist in database ('.$sql.')';
    			return false;
    		}
    	}
    	//end of variables checking
    	
    	//now get the latest attempt from this user on this LP, if available, otherwise create a new one
		//$lp_table = Database::get_course_table(LEARNPATH_VIEW_TABLE);
    	$lp_db = Database::get_current_course_database();
    	$lp_pref = Database::get_course_table_prefix();
    	$lp_table = $lp_db.'.'.$lp_pref.'lp_view';
		//selecting by view_count descending allows to get the highest view_count first
		$sql = "SELECT * FROM $lp_table WHERE lp_id = '$lp_id' AND user_id = '$user_id' ORDER BY view_count DESC";
		if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - querying lp_view: '.$sql,0);}
		//$res = Database::query($sql);
		$res = api_sql_query($sql);
		$view_id = 0; //used later to query lp_item_view
		if(Database::num_rows($res)>0)
		{
			if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - Found previous view',0);}
			$row = Database::fetch_array($res);
			$this->attempt = $row['view_count'];
			$this->lp_view_id = $row['id'];
			$this->last_item_seen = $row['last_item'];
			$this->progress_db = $row['progress'];
		}
		else
		{
			if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - NOT Found previous view',0);}
			$this->attempt = 1;
			$sql_ins = "INSERT INTO $lp_table (lp_id,user_id,view_count) VALUES ($lp_id,$user_id,1)";
			$res_ins = api_sql_query($sql_ins);
			$this->lp_view_id = Database::get_last_insert_id();
			if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - inserting new lp_view: '.$sql_ins,0);}
		}

    	//initialise items
    	$lp_item_table = $lp_db.'.'.$lp_pref.'lp_item';
		$sql = "SELECT * FROM $lp_item_table WHERE lp_id = '".$this->lp_id."' ORDER BY parent_item_id, display_order";
    	$res = api_sql_query($sql);
    	
    	while($row = Database::fetch_array($res))
    	{
			$oItem = '';
   			//$this->ordered_items[] = $row['id'];
   			switch($this->type){
				
   				case 3: //aicc
   					$oItem = new aiccItem('db',$row['id']);
   					if(is_object($oItem)){
   						$oItem->set_lp_view($this->lp_view_id);
		   				$oItem->set_prevent_reinit($this->prevent_reinit);
		   				$id = $oItem->get_id();
		   				// Don't use reference here as the next loop will make the pointed object change
		   				$this->items[$id] = $oItem;   						
		   				$this->refs_list[$oItem->ref]=$id;
		   				if($this->debug>2){error_log('New LP - learnpath::learnpath() - aicc object with id '.$id.' set in items[]',0);}
   					}
					break;
   				case 2:

   					require_once('scorm.class.php');
   					require_once('scormItem.class.php');
   					$oItem = new scormItem('db',$row['id']);   				
		   			if(is_object($oItem)){
		   				$oItem->set_lp_view($this->lp_view_id);
		   				$oItem->set_prevent_reinit($this->prevent_reinit);
		   				// Don't use reference here as the next loop will make the pointed object change
		   				$this->items[$oItem->get_id()] = $oItem;
		   				$this->refs_list[$oItem->ref]=$oItem->get_id();
		   				if($this->debug>2){error_log('New LP - object with id '.$oItem->get_id().' set in items[]',0);}
		   			}
   					break;

   				case 1:

   				default:
   					require_once('learnpathItem.class.php');
   					$oItem = new learnpathItem($row['id'],$user_id);
		   			if(is_object($oItem)){
		   				//$oItem->set_lp_view($this->lp_view_id); moved down to when we are sure the item_view exists
		   				$oItem->set_prevent_reinit($this->prevent_reinit);
		   				// Don't use reference here as the next loop will make the pointed object change
		   				$this->items[$oItem->get_id()] = $oItem;
		   				$this->refs_list[$oItem->get_id()]=$oItem->get_id();
		   				if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - object with id '.$oItem->get_id().' set in items[]',0);}
		   			}
   					break;
   			}

    		//items is a list of pointers to all items, classified by DB ID, not SCO id
    		if($row['parent_item_id'] == 0 OR empty($this->items[$row['parent_item_id']])){
   				$this->items[$row['id']]->set_level(0);
   			}else{
   				$level = $this->items[$row['parent_item_id']]->get_level()+1;
   				$this->items[$row['id']]->set_level($level);
   				if(is_object($this->items[$row['parent_item_id']])){
   					//items is a list of pointers from item DB ids to item objects
   					$this->items[$row['parent_item_id']]->add_child($row['id']);
   				}else{
   					if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - The parent item ('.$row['parent_item_id'].') of item '.$row['id'].' could not be found',0);}
   				}
   			}

	    	//get last viewing vars
	    	//$learnpath_items_view_table = Database::get_course_table(LEARNPATH_ITEM_VIEW_TABLE);
	    	$lp_item_view_table = $lp_db.'.'.$lp_pref.'lp_item_view';
	    	//this query should only return one or zero result
	    	$sql = "SELECT * " .
	    			"FROM $lp_item_view_table " .
	    			"WHERE lp_view_id = ".$this->lp_view_id." " .
	    			"AND lp_item_id = ".$row['id']." ORDER BY view_count DESC ";
	    	if($this->debug>2){error_log('New LP - learnpath::learnpath() - Selecting item_views: '.$sql,0);}
	    	//get the item status
	    	$res2 = api_sql_query($sql);
	    	if(Database::num_rows($res2)>0)
	    	{
	    		//if this learnpath has already been used by this user, get his last attempt count and
	    		//the last item seen back into this object
	    		//$max = 0;
	    		$row2 = Database::fetch_array($res2);
	    		if($this->debug>2){error_log('New LP - learnpath::learnpath() - Got item_view: '.print_r($row2,true),0);}
	    		$this->items[$row['id']]->set_status($row2['status']); 
	    		if(empty($row2['status'])){
		    		$this->items[$row['id']]->set_status($this->default_status); 
	    		}
	    		//$this->attempt = $row['view_count'];
	    		//$this->last_item = $row['id'];	    		
	    	}
			else //no item found in lp_item_view for this view
			{
				//first attempt from this user. Set attempt to 1 and last_item to 0 (first item available)
	    		//TODO  if the learnpath has not got attempts activated, always use attempt '1'
				//$this->attempt = 1;
				//$this->last_item = 0;
	    		$this->items[$row['id']]->set_status($this->default_status);
	    		//Add that row to the lp_item_view table so that we have something to show in the stats page
	    		$sql_ins = "INSERT INTO $lp_item_view_table " .
	    				"(lp_item_id, lp_view_id, view_count, status) VALUES " .
	    				"(".$row['id'].",".$this->lp_view_id.",1,'not attempted')";
	    		if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - Inserting blank item_view : '.$sql_ins,0);}
	    		$res_ins = api_sql_query($sql_ins);
			}
			//setting the view in the item object
			$this->items[$row['id']]->set_lp_view($this->lp_view_id);
    	}
    	$this->ordered_items = $this->get_flat_ordered_items_list($this->get_id(),0);
    	$this->max_ordered_items = 0;
    	foreach($this->ordered_items as $index=>$dummy){
    		if($index > $this->max_ordered_items AND !empty($dummy)){
    			$this->max_ordered_items = $index;
    		}
    	}
    	//TODO define the current item better
    	$this->first();
    	if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - End of learnpath constructor for learnpath '.$this->get_id(),0);}
    }
    
    /**
     * Function rewritten based on old_add_item() from Yannick Warnier. Due the fact that users can decide where the item should come, I had to overlook this function and
     * I found it better to rewrite it. Old function is still available. Added also the possibility to add a description.
     *
     * @param int $parent
     * @param int $previous
     * @param string $type
     * @param int $id
     * @param string $title
     * @param string $description
     * @return int
     */
    function add_item($parent, $previous, $type = 'dokeos_chapter', $id, $title, $description, $prerequisites=0)
    {
    	if($this->debug>0){error_log('New LP - In learnpath::add_item('.$parent.','.$previous.','.$type.','.$id.','.$title.')',0);}
    	
    	$tbl_lp_item = Database::get_course_table('lp_item');
    	$parent = intval($parent);
    	$previous = intval($previous);
    	$type = $this->escape_string($type);
    	$id = intval($id);
    	$title = $this->escape_string(htmlentities($title));
    	$description = $this->escape_string(htmlentities($description));
    	
    	$sql_count = "
    		SELECT COUNT(id) AS num
    		FROM " . $tbl_lp_item . "
    		WHERE
    			lp_id = " . $this->get_id() . " AND
    			parent_item_id = " . $parent;
    	
    	$res_count = api_sql_query($sql_count, __FILE__, __LINE__);
   		$row = Database::fetch_array($res_count);
   		
   		$num = $row['num'];
   		
   		if($num > 0)
   		{
	    	if($previous == 0)
	    	{
	    		$sql = "
	   				SELECT
	   					id,
	   					next_item_id,
	   					display_order
	   				FROM " . $tbl_lp_item . "
	   				WHERE
	   					lp_id = " . $this->get_id() . " AND
	   					parent_item_id = " . $parent . " AND
	   					previous_item_id = 0 OR previous_item_id=".$parent;
	    		
	    		$result = api_sql_query($sql, __FILE__, __LINE__);
	   			$row = Database::fetch_array($result);
	   			
	   			$tmp_previous = 0;
	   			$next = $row['id'];
	   			$display_order = 0;
	    	}
	    	else
	    	{
	   			$previous = (int) $previous;
	   			
	   			$sql = "
	   				SELECT
	   					id,
	   					previous_item_id,
	   					next_item_id,
	   					display_order
	   				FROM " . $tbl_lp_item . "
	   				WHERE
	   					lp_id = " . $this->get_id() . " AND
	   					id = " . $previous;
	   			
	   			$result = api_sql_query($sql, __FILE__, __LINE__);
	   			$row = Database::fetch_array($result);
	   			
	   			$tmp_previous = $row['id'];
	   			$next = $row['next_item_id'];
	   			
	   			$display_order = $row['display_order'];
	    	}
   		}
   		else
    	{
   			$tmp_previous = 0;
   			$next = 0;
   			$display_order = 0;
    	}
    	
    	$new_item_id = -1;
    	$id = $this->escape_string($id);
    	
    	if($type == 'quiz')
    	{
    		$sql = 'SELECT SUM(ponderation)
					FROM '.Database :: get_course_table(TABLE_QUIZ_QUESTION).' as quiz_question
					INNER JOIN  '.Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION).' as quiz_rel_question
						ON quiz_question.id = quiz_rel_question.question_id
						AND quiz_rel_question.exercice_id = '.$id;
			$rsQuiz = api_sql_query($sql, __FILE__, __LINE__);
			$max_score = mysql_result($rsQuiz, 0, 0);
    	}
    	else
    	{
    		$max_score = 100;
    	}
		
		if($prerequisites!=0){
			$sql_ins = "
	    		INSERT INTO " . $tbl_lp_item . " (
	    			lp_id,
	    			item_type,
	    			ref,
	    			title,
	    			description,
	    			path,
					max_score,
	    			parent_item_id,
	    			previous_item_id,
	    			next_item_id,
	    			display_order, 
					prerequisite
	    		) VALUES (
	    			" . $this->get_id() . ",
	    			'" . $type . "',
	    			'',
	    			'" . $title . "',
	    			'" . $description . "',
	    			'" . $id . "',
					'" . $max_score. "',
	    			" . $parent . ",
	    			" . $previous . ",
	    			" . $next . ",
	    			" . ($display_order + 1) . ",
	    			" . $prerequisites . "
	    		)";
		}
		
		else{
	    	//insert new item
	    	$sql_ins = "
	    		INSERT INTO " . $tbl_lp_item . " (
	    			lp_id,
	    			item_type,
	    			ref,
	    			title,
	    			description,
	    			path,
					max_score,
	    			parent_item_id,
	    			previous_item_id,
	    			next_item_id,
	    			display_order
	    		) VALUES (
	    			" . $this->get_id() . ",
	    			'" . $type . "',
	    			'',
	    			'" . $title . "',
	    			'" . $description . "',
	    			'" . $id . "',
					'" . $max_score. "',
	    			" . $parent . ",
	    			" . $previous . ",
	    			" . $next . ",
	    			" . ($display_order + 1) . "
	    		)";
		}
    	
    	if($this->debug>2){error_log('New LP - Inserting dokeos_chapter: '.$sql_ins,0);}

    	$res_ins = api_sql_query($sql_ins, __FILE__, __LINE__);
    	
    	if($res_ins > 0)
    	{
	    	$new_item_id = Database::get_last_insert_id($res_ins);
	    	
	    	//update the item that should come after the new item
	    	$sql_update_next = "
	    		UPDATE " . $tbl_lp_item . "
	    		SET previous_item_id = " . $new_item_id . "
	    		WHERE id = " . $next;
	    	
	    	$res_update_next = api_sql_query($sql_update_next, __FILE__, __LINE__);
	    	
	    	//update the item that should be before the new item
		    $sql_update_previous = "
		    	UPDATE " . $tbl_lp_item . "
		    	SET next_item_id = " . $new_item_id . "
		    	WHERE id = " . $tmp_previous;
		    
		    $res_update_previous = api_sql_query($sql_update_previous, __FILE__, __LINE__);
	    			   
	    	//update all the items after the new item
	    	$sql_update_order = "
	    		UPDATE " . $tbl_lp_item . "
	    		SET display_order = display_order + 1
	    		WHERE
	    			lp_id = " . $this->get_id() . " AND
	    			id <> " . $new_item_id . " AND
	    			parent_item_id = " . $parent . " AND
	    			display_order > " . $display_order;
	    	
	    	$res_update_previous = api_sql_query($sql_update_order, __FILE__, __LINE__);
	    	
	    	//update the item that should come after the new item
	    	$sql_update_ref = "
	    		UPDATE " . $tbl_lp_item . "
	    		SET ref = " . $new_item_id . "
	    		WHERE id = " . $new_item_id;
	    	
	    	api_sql_query($sql_update_ref, __FILE__, __LINE__);
	    	
    	}
    	
    	return $new_item_id;
    }
    
    
    /**
     * Adds an item to the current learnpath
     * @param	integer	Parent ID
     * @param	integer	Previous elem ID (0 if first)
     * @param	string	Resource type to add ('file','dokeos_item')
     * @param	string	Resource ID or path if 'file' or name if 'dokeos_chapter'
     * @return	integer	New element ID
     */
	function old_add_item($parent, $previous, $type = 'dokeos_chapter', $id, $title)
    {
    	//TODO
    	if($this->debug>0){error_log('New LP - In learnpath::add_item('.$parent.','.$previous.','.$type.','.$id.','.$title.')',0);}
    	
    	$tbl_lp_item = Database::get_course_table('lp_item');
    	$parent = (int) $parent;
    	$display_order = 1;
    	
    	if($previous == -1){
    		//insert in latest position available
    		$sql_check = "SELECT MAX(previous_item_id) as prev, id FROM $tbl_lp_item WHERE lp_id = " . $this->get_id() . " AND parent_item_id = " . $parent;
    		
    		if($this->debug>1){error_log('New LP - checking for latest item at this level: '.$sql_check,0);}
    		
    		$res = mysql_query($sql_check) or die(mysql_error());
    		
    		$res_check = api_sql_query($sql_check, __FILE__, __LINE__);
    		$num = Database::num_rows($res_check);
    		
    		if($num>0){
    			$row = Database::fetch_array($res_check);
    			$previous = $row['id'];
    		}else{
    			$previous = 0;
    		}
    	}else{
   			$previous = (int) $previous;
    	}
    	
    	$new_item_id = -1;
    	//check type
    	switch ($type){
			case 'dokeos_step':
    			$id = $this->escape_string($id);
				//check the next item
    			$sql_check = "SELECT * FROM $tbl_lp_item " .
    					"WHERE lp_id = ".$this->get_id()." " .
    							"AND previous_item_id = $previous " .
    							"AND parent_item_id = $parent";
    			if($this->debug>2){error_log('New LP - Getting info from the next element: '.$sql_check,0);}
				$res_check = api_sql_query($sql_check);
				if(Database::num_rows($res_check)){
					$row = Database::fetch_array($res_check);
					$next = $row['id'];
					//TODO check display_order
					$display_order = $row['display_order']+1;
				}else{
					$next = 0;
					$display_order = 1;
				}
				
				//insert new item
    			$sql_ins = "INSERT INTO $tbl_lp_item " .
    					"(lp_id,item_type,ref,title," .
    					"path,parent_item_id," .
    					"previous_item_id,next_item_id,display_order)" .
    					"VALUES" .
    					"(".$this->get_id().",'$type','','$title','$id'," .
    					"$parent," .
    					"$previous,$next,$display_order)";
    			if($this->debug>2){error_log('New LP - Inserting dokeos_chapter: '.$sql_ins,0);}
    			$res_ins = api_sql_query($sql_ins);
    			if($res_ins>0){
	    			$new_item_id = Database::get_last_insert_id($res_ins);
	    			//now update previous item
	    			$sql_upd = "UPDATE $tbl_lp_item " .
	    					"SET next_item_id = $new_item_id " .
	    					"WHERE lp_id = ".$this->get_id()." " .
	    							"AND id = $previous AND parent_item_id = $parent";

	    			if($this->debug>2){error_log('New LP - Updating previous item: '.$sql_upd,0);}

	    			$res_upd = api_sql_query($sql_upd);

	    			//now update next item

	    			$sql_upd = "UPDATE $tbl_lp_item " .

	    					"SET previous_item_id = $new_item_id " .

	    					"WHERE lp_id = ".$this->get_id()." " .

	    							"AND id = $next AND parent_item_id = $parent";

	    			if($this->debug>2){error_log('New LP - Updating next item: '.$sql_upd,0);}

	    			$res_upd = api_sql_query($sql_upd);
	    			
    			}

	    		break;

    		default:

    			break;

    	}

    	return $new_item_id;

    }

    /**

     * Static admin function allowing addition of a learnpath to a course.

     * @param	string	Course code

     * @param	string	Learnpath name

     * @param	string	Learnpath description string, if provided

     * @param	string	Type of learnpath (default = 'guess', others = 'dokeos', 'aicc',...)

     * @param	string	Type of files origin (default = 'zip', others = 'dir','web_dir',...)

     * @param	string	Zip file containing the learnpath or directory containing the learnpath

     * @return	integer	The new learnpath ID on success, 0 on failure

     */

    function add_lp($course,$name,$description='',$learnpath='guess',$origin='zip',$zipname='')

    {

		//if($this->debug>0){error_log('New LP - In learnpath::add_lp()',0);}

    	//TODO

    	$tbl_lp = Database::get_course_table('lp');

    	//check course code exists

    	//check lp_name doesn't exist, otherwise append something

    	$i = 0;

    	$name = learnpath::escape_string(htmlentities($name)); //Kevin Van Den Haute: added htmlentities()

    	$check_name = "SELECT * FROM $tbl_lp WHERE name = '$name'";

    	//if($this->debug>2){error_log('New LP - Checking the name for new LP: '.$check_name,0);}

    	$res_name = api_sql_query($check_name);

		while(Database::num_rows($res_name)){

    		//there is already one such name, update the current one a bit

    		$i++;

    		$name = $name.' - '.$i;

	    	$check_name = "SELECT * FROM $tbl_lp WHERE name = '$name'";

	    	//if($this->debug>2){error_log('New LP - Checking the name for new LP: '.$check_name,0);}

	    	$res_name = api_sql_query($check_name);

    	}

    	//new name does not exist yet; keep it

    	//escape description

    	$description = learnpath::escape_string(htmlentities($description)); //Kevin: added htmlentities()

    	$type = 1;

    	switch($learnpath){

    		case 'guess':

    			break;

    		case 'dokeos':

    			$type = 1;

    			break;

    		case 'aicc':

    			break;

    	}

    	switch($origin){

    		case 'zip':

	    		//check zipname string. If empty, we are currently creating a new Dokeos learnpath

    			break;

    		case 'manual':

		    	$get_max = "SELECT MAX(display_order) FROM $tbl_lp";

		    	$res_max = api_sql_query($get_max);

		    	if(Database::num_rows($res_max)<1){

		    		$dsp = 1;

		    	}else{

		    		$row = Database::fetch_array($res_max);

		    		$dsp = $row[0]+1;

		    	}

    			$sql_insert = "INSERT INTO $tbl_lp " .

    					"(lp_type,name,description,path,default_view_mod," .

    					"default_encoding,display_order,content_maker," .

    					"content_local,js_lib) " .

    					"VALUES ($type,'$name','$description','','embedded'," .

    					"'ISO-8859-1','$dsp','Dokeos'," .

    					"'local','')";

    			//if($this->debug>2){error_log('New LP - Inserting new lp '.$sql_insert,0);}
				
    			$res_insert = api_sql_query($sql_insert);

				$id = Database::get_last_insert_id();
				
				if($id>0){

					return $id;

				}

    			break;

    	}

    }

    /**

     * Appends a message to the message attribute

     * @param	string	Message to append.

     */

    function append_message($string)

    {

		if($this->debug>0){error_log('New LP - In learnpath::append_message()',0);}

    	$this->message .= $string;

    }

    /**

     * Autocompletes the parents of an item in case it's been completed or passed

     * @param	integer	Optional ID of the item from which to look for parents

     */

    function autocomplete_parents($item)

    {

		if($this->debug>0){error_log('New LP - In learnpath::autocomplete_parents()',0);}

    	if(empty($item)){

    		$item = $this->current;

    	}

    	$parent_id = $this->items[$item]->get_parent();

    	if($this->debug>2){error_log('New LP - autocompleting parent of item '.$item.' (item '.$parent_id.')',0);}

    	if(is_object($this->items[$item]) and !empty($parent_id))

    	{//if $item points to an object and there is a parent

    		if($this->debug>2){error_log('New LP - '.$item.' is an item, proceed',0);}

    		$current_item =& $this->items[$item];

    		$parent =& $this->items[$parent_id]; //get the parent

			//new experiment including failed and browsed in completed status
			$current_status = $current_item->get_status();
    		if($current_item->is_done() || $current_status=='browsed' || $current_status=='failed'){

    			//if the current item is completed or passes or succeeded

    			$completed = true;

    			if($this->debug>2){error_log('New LP - Status of current item is alright',0);}

    			foreach($parent->get_children() as $child){

    				//check all his brothers (his parent's children) for completion status

    				if($child!= $item){

    					if($this->debug>2){error_log('New LP - Looking at brother with ID '.$child.', status is '.$this->items[$child]->get_status(),0);}

    					//if($this->items[$child]->status_is(array('completed','passed','succeeded')))
    					//Trying completing parents of failed and browsed items as well
    					if($this->items[$child]->status_is(array('completed','passed','succeeded','browsed','failed')))
    					{

    						//keep completion status to true

    					}else{

    						if($this->debug>2){error_log('New LP - Found one incomplete child of '.$parent_id.': '.$child.' is '.$this->items[$child]->get_status(),0);}

    						$completed = false;

    					}

    				}

    			}

    			if($completed == true){ //if all the children were completed

	    			$parent->set_status('completed');

	    			$parent->save(false);

    				$this->update_queue[$parent->get_id()] = $parent->get_status();

    				if($this->debug>2){error_log('New LP - Added parent to update queue '.print_r($this->update_queue,true),0);}

	    			$this->autocomplete_parents($parent->get_id()); //recursive call

    			}

    		}else{

    			//error_log('New LP - status of current item is not enough to get bothered',0);

    		}

    	}

    }

    /**

     * Autosaves the current results into the database for the whole learnpath

     */

    function autosave()

    {

		if($this->debug>0){error_log('New LP - In learnpath::autosave()',0);}

    	//TODO add aditionnal save operations for the learnpath itself

    }

    /**

     * Clears the message attribute

     */

    function clear_message()

    {

		if($this->debug>0){error_log('New LP - In learnpath::clear_message()',0);}

    	$this->message = '';

    }

    /**

     * Closes the current resource

     *

     * Stops the timer

     * Saves into the database if required

     * Clears the current resource data from this object

     * @return	boolean	True on success, false on failure

     */

    function close()

    {

		if($this->debug>0){error_log('New LP - In learnpath::close()',0);}

    	if(empty($this->lp_id))

    	{

    		$this->error = 'Trying to close this learnpath but no ID is set';

    		return false;

    	}

    	$this->current_time_stop = time();

    	if($this->save)

    	{

	    	$learnpath_view_table = Database::get_course_table(LEARNPATH_VIEW_TABLE);

	    	/*

	    	$sql = "UPDATE $learnpath_view_table " .

	    			"SET " .

	    			"stop_time = ".$this->current_time_stop.", " .

	    			"score = ".$this->current_score.", ".

	    			"WHERE learnpath_id = '".$this->lp_id."'";

	    	//$res = Database::query($sql);

	    	$res = api_sql_query($res);

	    	if(mysql_affected_rows($res)<1)

	    	{

	    		$this->error = 'Could not update learnpath_view table while closing learnpath';

	    		return false;

	    	}

	    	*/    		

    	}

    	$this->ordered_items = array();

    	$this->index=0;

    	unset($this->lp_id);

    	//unset other stuff

    	return true;

    }

    /**

     * Static admin function allowing removal of a learnpath

     * @param	string	Course code

     * @param	integer	Learnpath ID

     * @param	string	Whether to delete data or keep it (default: 'keep', others: 'remove')

     * @return	boolean	True on success, false on failure (might change that to return number of elements deleted)

     */

    function delete($course=null,$id=null,$delete='keep')

    {

    	//TODO implement a way of getting this to work when the current object is not set

    	//In clear: implement this in the item class as well (abstract class) and use the given ID in queries

    	//if(empty($course)){$course = api_get_course_id();}

    	//if(empty($id)){$id = $this->get_id();}

    	

		//if($this->debug>0){error_log('New LP - In learnpath::delete()',0);}

    	foreach($this->items as $id => $dummy)

    	{

    		$this->items[$id]->delete();

    	}

    	$lp = Database::get_course_table('lp');

    	$lp_view = Database::get_course_table('lp_view');

    	$sql_del_view = "DELETE FROM $lp_view WHERE lp_id = ".$this->lp_id;

    	//if($this->debug>2){error_log('New LP - Deleting views bound to lp '.$this->lp_id.': '.$sql_del_view,0);}

    	$res_del_view = api_sql_query($sql_del_view);

    	//if($this->debug>2){error_log('New LP - Deleting lp '.$this->lp_id.' of type '.$this->type,0);}

    	if($this->type == 2 OR $this->type==3){
    		//this is a scorm learning path, delete the files as well
    		$sql = "SELECT path FROM $lp WHERE id = ".$this->lp_id;
    		$res = api_sql_query($sql);
    		if(Database::num_rows($res)>0){
    			$row = Database::fetch_array($res);
    			$path = $row['path'];
    			$sql = "SELECT id FROM $lp WHERE path = '$path' AND id != ".$this->lp_id;
    			$res = api_sql_query($sql);
    			if(Database::num_rows($res)>0)
    			{ //another learning path uses this directory, so don't delete it 
    				if($this->debug>2){error_log('New LP - In learnpath::delete(), found other LP using path '.$path.', keeping directory',0);}
    			}else{
    				//no other LP uses that directory, delete it
			     	$course_rel_dir  = api_get_course_path().'/scorm/'; //scorm dir web path starting from /courses
					$course_scorm_dir = api_get_path(SYS_COURSE_PATH).$course_rel_dir; //absolute system path for this course
    				if($delete == 'remove' && is_dir($course_scorm_dir.$path) and !empty($course_scorm_dir)){
    					if($this->debug>2){error_log('New LP - In learnpath::delete(), found SCORM, deleting directory: '.$course_scorm_dir.$path,0);}
    					exec('rm -rf '.$course_scorm_dir.$path);
    				}
    			}
    		}
    	}
    	$sql_del_lp = "DELETE FROM $lp WHERE id = ".$this->lp_id;
    	//if($this->debug>2){error_log('New LP - Deleting lp '.$this->lp_id.': '.$sql_del_lp,0);}
    	$res_del_lp = api_sql_query($sql_del_lp);
    	//TODO: also delete items and item-views
    }

    /**

     * Removes all the children of one item - dangerous!

     * @param	integer	Element ID of which children have to be removed

     * @return	integer	Total number of children removed

     */

    function delete_children_items($id){

		if($this->debug>0){error_log('New LP - In learnpath::delete_children_items('.$id.')',0);}

    	$num = 0;

    	if(empty($id) || $id != strval(intval($id))){return false;}

		$lp_item = Database::get_course_table('lp_item');

		$sql = "SELECT * FROM $lp_item WHERE parent_item_id = $id";

		$res = api_sql_query($sql);

		while($row = Database::fetch_array($res)){

			$num += $this->delete_children_items($row['id']);

			$sql_del = "DELETE FROM $lp_item WHERE id = ".$row['id'];

			$res_del = api_sql_query($sql_del);

			$num++;

		}

		return $num;

    }

    /**

     * Removes an item from the current learnpath

     * @param	integer	Elem ID (0 if first)

     * @param	integer	Whether to remove the resource/data from the system or leave it (default: 'keep', others 'remove')

     * @return	integer	Number of elements moved

     * @todo implement resource removal

     */

    function delete_item($id, $remove='keep')

    {

		if($this->debug>0){error_log('New LP - In learnpath::delete_item()',0);}

    	//TODO - implement the resource removal

    	if(empty($id) || $id != strval(intval($id))){return false;}

    	//first select item to get previous, next, and display order

		$lp_item = Database::get_course_table('lp_item');

    	$sql_sel = "SELECT * FROM $lp_item WHERE id = $id";

    	$res_sel = api_sql_query($sql_sel,__FILE__,__LINE__);

    	if(Database::num_rows($res_sel)<1){return false;}

    	$row = Database::fetch_array($res_sel);

    	$previous = $row['previous_item_id'];

    	$next = $row['next_item_id'];

    	$display = $row['display_order'];

    	$parent = $row['parent_item_id'];

    	$lp = $row['lp_id'];

    	//delete children items

    	$num = $this->delete_children_items($id);

    	if($this->debug>2){error_log('New LP - learnpath::delete_item() - deleted '.$num.' children of element '.$id,0);}

    	//now delete the item

    	$sql_del = "DELETE FROM $lp_item WHERE id = $id";

    	if($this->debug>2){error_log('New LP - Deleting item: '.$sql_del,0);}

    	$res_del = api_sql_query($sql_del,__FILE__,__LINE__);

    	//now update surrounding items

    	$sql_upd = "UPDATE $lp_item SET next_item_id = $next WHERE id = $previous";

    	$res_upd = api_sql_query($sql_upd,__FILE__,__LINE__);

    	$sql_upd = "UPDATE $lp_item SET previous_item_id = $previous WHERE id = $next";

    	$res_upd = api_sql_query($sql_upd,__FILE__,__LINE__);

    	//now update all following items with new display order

    	$sql_all = "UPDATE $lp_item SET display_order = display_order-1 WHERE lp_id = $lp AND parent_item_id = $parent AND display_order > $display";

    	$res_all = api_sql_query($sql_all,__FILE__,__LINE__);

    }

    /**

     * Updates an item's content in place

     * @param	integer	Element ID

	 * @param	string	New content

     * @return	boolean	True on success, false on error

     */

    function old_edit_item($id,$content)

    {

		if($this->debug>0){error_log('New LP - In learnpath::edit_item()',0);}

    	if(empty($id) or ($id != strval(intval($id))) or empty($content)){ return false; }

    	$content = $this->escape_string($content);

		$lp_item = Database::get_course_table('lp_item');

    	$sql_upd = "UPDATE $lp_item SET title = '$content' WHERE id = $id";

    	$res_upd = api_sql_query($sql_upd,__FILE__,__LINE__);

    	//TODO update the item object (can be ignored for now because refreshed)

    	return true;

    }
    
    function edit_item($id, $parent, $previous, $title, $description, $prerequisites=0)
    {
    	if($this->debug > 0){error_log('New LP - In learnpath::edit_item()', 0);}

    	if(empty($id) or ($id != strval(intval($id))) or empty($title)){ return false; }
    	
    	$tbl_lp_item = Database::get_course_table('lp_item');
    	
    	$sql_select = "
    		SELECT *
    		FROM " . $tbl_lp_item . "
    		WHERE id = " . $id;
    	$res_select = api_sql_query($sql_select, __FILE__, __LINE__);
    	$row_select = Database::fetch_array($res_select);
    	
    	$same_parent	= ($row_select['parent_item_id'] == $parent) ? true : false;
    	$same_previous	= ($row_select['previous_item_id'] == $previous) ? true : false;
    	
    	if($same_parent && $same_previous)
    	{
    		//only update title and description
    		$sql_update = "
    			UPDATE " . $tbl_lp_item . "
    			SET
    				title = '" . $this->escape_string(htmlentities($title)) . "',
					prerequisite = '".$prerequisites."',
    				description = '" . $this->escape_string(htmlentities($description)) . "'
    			WHERE id = " . $id;
    		$res_update = api_sql_query($sql_update, __FILE__, __LINE__);
    	}
    	else
    	{
    		$old_parent		 = $row_select['parent_item_id'];
    		$old_previous	 = $row_select['previous_item_id'];
    		$old_next		 = $row_select['next_item_id'];
    		$old_order		 = $row_select['display_order'];
    		$old_prerequisite= $row_select['prerequisite'];
    		
    		/* BEGIN -- virtually remove the current item id */
    		/* for the next and previous item it is like the current item doesn't exist anymore */
    		
    		if($old_previous != 0)
    		{
	    		$sql_update_next = "
		    		UPDATE " . $tbl_lp_item . "
		    		SET next_item_id = " . $old_next . "
		    		WHERE id = " . $old_previous;
		    	$res_update_next = api_sql_query($sql_update_next, __FILE__, __LINE__);
	    	
	    		//echo '<p>' . $sql_update_next . '</p>';
    		}
    		
    		if($old_next != 0)
    		{
		    	$sql_update_previous = "
		    		UPDATE " . $tbl_lp_item . "
		    		SET previous_item_id = " . $old_previous . "
		    		WHERE id = " . $old_next;
		    	$res_update_previous = api_sql_query($sql_update_previous, __FILE__, __LINE__);
		    	
		    	//echo '<p>' . $sql_update_previous . '</p>';
    		}
    		
    		//display_order - 1 for every item with a display_order bigger then the display_order of the current item
	    	$sql_update_order = "
	    		UPDATE " . $tbl_lp_item . "
	    		SET display_order = display_order - 1
	    		WHERE
	    			display_order > " . $old_order . " AND
	    			parent_item_id = " . $old_parent;
	    	$res_update_order = api_sql_query($sql_update_order, __FILE__, __LINE__);
	    	
	    	//echo '<p>' . $sql_update_order . '</p>';
	    	
    		/* END -- virtually remove the current item id */
    		
    		/* BEGIN -- update the current item id to his new location */
    		
    		if($previous == 0)
    		{
	    		//select the data of the item that should come after the current item
	    		$sql_select_old = "
	    			SELECT
	    				id,
	    				display_order
	    			FROM " . $tbl_lp_item . "
	    			WHERE
	    				lp_id = " . $this->lp_id . " AND
	    				parent_item_id = " . $parent . " AND
	    				previous_item_id = " . $previous;
	    		$res_select_old = api_sql_query($sql_select_old, __FILE__, __LINE__);
		    	$row_select_old = Database::fetch_array($res_select_old);
		    	
		    	//echo '<p>' . $sql_select_old . '</p>';
		    	
		    	//if the new parent doesn't had children before
		    	if(Database::num_rows($res_select_old) == 0)
		    	{
		    		$new_next	= 0;
		    		$new_order	= 1;
		    	}
		    	else
		    	{
		    		$new_next	= $row_select_old['id'];
		    		$new_order	= $row_select_old['display_order'];
		    	}
		    	
		    	//echo 'New next_item_id of current item: ' . $new_next . '<br />';
		    	//echo 'New previous_item_id of current item: ' . $previous . '<br />';
		    	//echo 'New display_order of current item: ' . $new_order . '<br />';
	    		
		    	
    		}
    		else
    		{
    			//select the data of the item that should come before the current item
	    		$sql_select_old = "
	    			SELECT
	    				next_item_id,
	    				display_order
	    			FROM " . $tbl_lp_item . "
	    			WHERE id = " . $previous;
	    		$res_select_old = api_sql_query($sql_select_old, __FILE__, __LINE__);
		    	$row_select_old = Database::fetch_array($res_select_old);
		    	
		    	//echo '<p>' . $sql_select_old . '</p>';
		    	
		    	//echo 'New next_item_id of current item: ' . $row_select_old['next_item_id'] . '<br />';
		    	//echo 'New previous_item_id of current item: ' . $previous . '<br />';
		    	//echo 'New display_order of current item: ' . ($row_select_old['display_order'] + 1) . '<br />';
	    		
		    	$new_next	= $row_select_old['next_item_id'];
		    	$new_order	= $row_select_old['display_order'] + 1;
    		}
	    	
    		//update the current item with the new data
    		$sql_update = "
	    		UPDATE " . $tbl_lp_item . "
	    		SET
	    			title = '" . $this->escape_string(htmlentities($title)) . "',
	    			description = '" . $this->escape_string(htmlentities($description)) . "',
	    			parent_item_id = " . $parent . ",
	    			previous_item_id = " . $previous . ",
	    			next_item_id = " . $new_next . ",
	    			display_order = " . $new_order . "
	    		WHERE id = " . $id;
    		$res_update_next = api_sql_query($sql_update, __FILE__, __LINE__);
    		//echo '<p>' . $sql_update . '</p>';
    		
    		if($previous != 0)
    		{
    			//update the previous item his next_item_id
	    		$sql_update_previous = "
			    	UPDATE " . $tbl_lp_item . "
			    	SET next_item_id = " . $id . "
			    	WHERE id = " . $previous;
	    		$res_update_next = api_sql_query($sql_update_previous, __FILE__, __LINE__);
	    		//echo '<p>' . $sql_update_previous . '</p>';
    		}
    		
    		if($new_next != 0)
    		{
       			//update the next item his previous_item_id
	    		$sql_update_next = "
			    	UPDATE " . $tbl_lp_item . "
			    	SET previous_item_id = " . $id . "
			    	WHERE id = " . $new_next;
			  	$res_update_next = api_sql_query($sql_update_next, __FILE__, __LINE__);
			  	//echo '<p>' . $sql_update_next . '</p>';
    		}
    		
    		if($old_prerequisite!=$prerequisites){
    			$sql_update_next = "
		    		UPDATE " . $tbl_lp_item . "
		    		SET prerequisite = " . $prerequisites . "
		    		WHERE id = " . $id;
		    	$res_update_next = api_sql_query($sql_update_next, __FILE__, __LINE__);
    		}
    		
    		//update all the items with the same or a bigger display_order then the current item
	    	$sql_update_order = "
			   	UPDATE " . $tbl_lp_item . "
			   	SET display_order = display_order + 1
			   	WHERE
			   		lp_id = " . $this->get_id() . " AND
			   		id <> " . $id . " AND
			   		parent_item_id = " . $parent . " AND
			   		display_order >= " . $new_order;
	    	
	    	$res_update_next = api_sql_query($sql_update_order, __FILE__, __LINE__);
			//echo '<p>' . $sql_update_order . '</p>';
    		
    		/* END -- update the current item id to his new location */
    	}
    }
    
    /**

     * Updates an item's prereq in place

     * @param	integer	Element ID

	 * @param	string	Prerequisite Element ID
	 * 
	 * @param	string	Prerequisite item type
	 * 
	 * @param	string	Prerequisite min score
	 * 
	 * @param	string	Prerequisite max score

     * @return	boolean	True on success, false on error

     */

    function edit_item_prereq($id, $prerequisite_id, $min_score = 0, $max_score = 100)

    {

		if($this->debug>0){error_log('New LP - In learnpath::edit_item_prereq()',0);}

    	if(empty($id) or ($id != strval(intval($id))) or empty($prerequisite_id)){ return false; }

    	$prerequisite_id = $this->escape_string($prerequisite_id);

		$tbl_lp_item = Database::get_course_table('lp_item');
		
		if(!is_numeric($min_score) || $min_score < 0 || $min_score > 100)
			$min_score = 0;
		
		if(!is_numeric($max_score) || $max_score < 0 || $max_score > 100)
			$max_score = 100;
		
		if($min_score > $max_score)
			$max_score = $min_score;
		
		if(!is_numeric($prerequisite_id))
			$prerequisite_id = 'NULL';
			
    	$sql_upd = "
    		UPDATE " . $tbl_lp_item . "
    		SET
    			min_score = " . $min_score . ",
    			max_score = " . $max_score . ",
    			prerequisite = " . $prerequisite_id . "
    		WHERE id = " . $id;
    	
    	$res_upd = api_sql_query($sql_upd ,__FILE__, __LINE__);

    	//TODO update the item object (can be ignored for now because refreshed)

    	return true;

    }

    /**

     * Escapes a string with the available database escape function

     * @param	string	String to escape

     * @return	string	String escaped

     */

    function escape_string($string){

		//if($this->debug>0){error_log('New LP - In learnpath::escape_string('.$string.')',0);}

    	return mysql_real_escape_string($string);

    }

    /**

     * Static admin function exporting a learnpath into a zip file

     * @param	string	Export type (scorm, zip, cd)

     * @param	string	Course code

     * @param	integer Learnpath ID

     * @param	string	Zip file name

     * @return	string	Zip file path (or false on error)

     */

    function export_lp($type, $course, $id, $zipname)

    {

		//if($this->debug>0){error_log('New LP - In learnpath::export_lp()',0);}

    	//TODO

		if(empty($type) OR empty($course) OR empty($id) OR empty($zipname)){return false;}

		$url = '';

    	switch($type){

    		case 'scorm':
				
    			break;

    		case 'zip':

    			break;

    		case 'cdrom':

    			break;

    	}

    	return $url;

    }

    /**

     * Gets all the chapters belonging to the same parent as the item/chapter given

     * Can also be called as abstract method

     * @param	integer	Item ID

     * @return	array	A list of all the "brother items" (or an empty array on failure)

     */

    function get_brother_chapters($id){

		if($this->debug>0){error_log('New LP - In learnpath::get_brother_chapters()',0);}

    	if(empty($id) OR $id != strval(intval($id))){ return array();}

    	$lp_item = Database::get_course_table('lp_item');

    	$sql_parent = "SELECT * FROM $lp_item WHERE id = $id AND item_type='dokeos_chapter'";

    	$res_parent = api_sql_query($sql_parent,__FILE__,__LINE__);

    	if(Database::num_rows($res_parent)>0){

    		$row_parent = Database::fetch_array($res_parent);

    		$parent = $row_parent['parent_item_id'];

    		$sql_bros = "SELECT * FROM $lp_item WHERE parent_item_id = $parent AND id = $id AND item_type='dokeos_chapter' ORDER BY display_order";

    		$res_bros = api_sql_query($sql_bros,__FILE__,__LINE__);

    		$list = array();

    		while ($row_bro = Database::fetch_array($res_bros)){

    			$list[] = $row_bro;

    		}

    		return $list;

    	}

    	return array();

    }

    /**

     * Gets all the items belonging to the same parent as the item given

     * Can also be called as abstract method

     * @param	integer	Item ID

     * @return	array	A list of all the "brother items" (or an empty array on failure)

     */

    function get_brother_items($id){

		if($this->debug>0){error_log('New LP - In learnpath::get_brother_items('.$id.')',0);}

    	if(empty($id) OR $id != strval(intval($id))){ return array();}

    	$lp_item = Database::get_course_table('lp_item');

    	$sql_parent = "SELECT * FROM $lp_item WHERE id = $id";

    	$res_parent = api_sql_query($sql_parent,__FILE__,__LINE__);

    	if(Database::num_rows($res_parent)>0){

    		$row_parent = Database::fetch_array($res_parent);

    		$parent = $row_parent['parent_item_id'];

    		$sql_bros = "SELECT * FROM $lp_item WHERE parent_item_id = $parent ORDER BY display_order";

    		$res_bros = api_sql_query($sql_bros,__FILE__,__LINE__);

    		$list = array();

    		while ($row_bro = Database::fetch_array($res_bros)){

    			$list[] = $row_bro;

    		}

    		return $list;

    	}

    	return array();

    }

    /**

     * Gets the number of items currently completed

     * @return integer The number of items currently completed

     */

    function get_complete_items_count()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_complete_items_count()',0);}

       	$i = 0;

    	foreach($this->items as $id => $dummy){

    		//if($this->items[$id]->status_is(array('completed','passed','succeeded'))){
    		//Trying failed and browsed considered "progressed" as well
    		if($this->items[$id]->status_is(array('completed','passed','succeeded','browsed','failed'))&&$this->items[$id]->get_type()!='dokeos_chapter'&&$this->items[$id]->get_type()!='dir'){

    			$i++;

    		}

    	}

    	return $i;

    }

    /**
     * Gets the current item ID
     * @return	integer	The current learnpath item id
     */
    function get_current_item_id()
    {
		$current = 0;
		if($this->debug>0){error_log('New LP - In learnpath::get_current_item_id()',0);}
    	if(!empty($this->current))
    	{
    		$current = $this->current;
    	}
		if($this->debug>2){error_log('New LP - In learnpath::get_current_item_id() - Returning '.$current,0);}
    	return $current;
    }
    /**
     * Gets the total number of items available for viewing in this SCORM
     * @return	integer	The total number of items
     */
    function get_total_items_count()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_total_items_count()',0);}
    	return count($this->items);
    }
    /**
     * Gets the total number of items available for viewing in this SCORM but without chapters
     * @return	integer	The total no-chapters number of items
     */
    function get_total_items_count_without_chapters()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_total_items_count_without_chapters()',0);}
		$total=0;
		foreach($this->items as $temp=>$temp2){
			if(!in_array($temp2->get_type(), array('dokeos_chapter','chapter','dir'))) $total++;
		}
		return $total;
    }
	/**
     * Gets the first element URL.
     * @return	string	URL to load into the viewer
     */
    function first()
    {
		if($this->debug>0){error_log('New LP - In learnpath::first()',0);}
		//test if the last_item_seen exists and is not a dir
    	if(!empty($this->last_item_seen)
    		&& !empty($this->items[$this->last_item_seen])
    		&& $this->items[$this->last_item_seen]->get_type() != 'dir'
    		&& $this->items[$this->last_item_seen]->get_type() != 'dokeos_chapter'
    		&& $this->items[$this->last_item_seen]->is_done() != true
    	){
    		if($this->debug>2){error_log('New LP - In learnpath::first() - Last item seen is '.$this->last_item_seen.' of type '.$this->items[$this->last_item_seen]->get_type(),0);}
    		$index = -1;
    		foreach($this->ordered_items as $myindex => $item_id){
    			if($item_id == $this->last_item_seen){
	    			$index = $myindex;	
    				break;
    			}
    		}
    		if($index==-1){
    			//index hasn't changed, so item not found - panic (this shouldn't happen)
    			if($this->debug>2){error_log('New LP - Last item ('.$this->last_item_seen.') was found in items but not in ordered_items, panic!',0);}
    			return false;
    		}else{
    			$this->last = $this->last_item_seen;
    			$this->current = $this->last_item_seen;
    			$this->index = $index;
    		}
    	}else{
    		if($this->debug>2){error_log('New LP - In learnpath::first() - No last item seen',0);}
	    	$index = 0;
	    	while (!empty($this->ordered_items[$index]) 
	    		AND 
	    		(
	    			$this->items[$this->ordered_items[$index]]->get_type() == 'dir'
	    		  	OR $this->items[$this->ordered_items[$index]]->get_type() == 'dokeos_chapter'
	    		  	OR $this->items[$this->ordered_items[$index]]->is_done() === true
	    		)
				AND $index < $this->max_ordered_items)
	    	{
		   		$index ++;
	    	}
	    	$this->last = $this->current;
	    	//current is 
	    	$this->current = $this->ordered_items[$index];
	    	$this->index = $index;
    		if($this->debug>2){error_log('New LP - In learnpath::first() - No last item seen. New last = '.$this->last.'('.$this->ordered_items[$index].')',0);}
    	}
    	if($this->debug>2){error_log('New LP - In learnpath::first() - First item is '.$this->get_current_item_id());}
    }

    /**

     * Gets the information about an item in a format usable as JavaScript to update

     * the JS API by just printing this content into the <head> section of the message frame

	 * @param	integer		Item ID

     * @return	string

     */

    function get_js_info($item_id=''){

		if($this->debug>0){error_log('New LP - In learnpath::get_js_info('.$item_id.')',0);}

		$info = '';

    	$item_id = $this->escape_string($item_id);

    	if(!empty($item_id) && is_object($this->items[$item_id])){

    		//if item is defined, return values from DB

    		$oItem = $this->items[$item_id];

    		$info .= '<script language="javascript">';

			$info .= "top.set_score(".$oItem->get_score().");\n";

			$info .= "top.set_max(".$oItem->get_max().");\n";

			$info .= "top.set_min(".$oItem->get_min().");\n";

			$info .= "top.set_lesson_status('".$oItem->get_status()."');";

			$info .= "top.set_session_time('".$oItem->get_scorm_time('js')."');";

			$info .= "top.set_suspend_data('".$oItem->get_suspend_data()."');";

			$info .= "top.set_saved_lesson_status('".$oItem->get_status()."');";

			$info .= "top.set_flag_synchronized();";

    		$info .= '</script>';

			if($this->debug>2){error_log('New LP - in learnpath::get_js_info('.$item_id.') - returning: '.$info,0);}

    		return $info;

    	}else{

    		//if item_id is empty, just update to default SCORM data

    		$info .= '<script language="javascript">';

			$info .= "top.set_score(".learnpathItem::get_score().");\n";

			$info .= "top.set_max(".learnpathItem::get_max().");\n";

			$info .= "top.set_min(".learnpathItem::get_min().");\n";

			$info .= "top.set_lesson_status('".learnpathItem::get_status()."');";

			$info .= "top.set_session_time('".learnpathItem::get_scorm_time('js')."');";

			$info .= "top.set_suspend_data('".learnpathItem::get_suspend_data()."');";

			$info .= "top.set_saved_lesson_status('".learnpathItem::get_status()."');";

			$info .= "top.set_flag_synchronized();";

    		$info .= '</script>';

			if($this->debug>2){error_log('New LP - in learnpath::get_js_info('.$item_id.') - returning: '.$info,0);}

    		return $info;

    	}

    }
    /**
     * Gets the js library from the database
     * @return	string	The name of the javascript library to be used
     */
	function get_js_lib(){
		$lib = '';
		if(!empty($this->js_lib)){
			$lib = $this->js_lib;
		}
		return $lib;
	}
    /**

     * Gets the learnpath database ID

     * @return	integer	Learnpath ID in the lp table

     */

    function get_id()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_id()',0);}

    	if(!empty($this->lp_id))

    	{

    		return $this->lp_id;

    	}else{

    		return 0;

    	}

    }

    /**

     * Gets the last element URL.

     * @return string URL to load into the viewer

     */

    function get_last()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_last()',0);}

    	$this->index = count($this->ordered_items)-1;

    	return $this->ordered_items[$this->index];

    }

    /**

     * Gets the navigation bar for the learnpath display screen

     * @return	string	The HTML string to use as a navigation bar

     */

    function get_navigation_bar()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_navigation_bar()',0);}

    	//TODO find a good value for the following variables

    	$file = '';

    	$openDir = '';

    	$edoceo = '';

    	$time = 0;

    	$navbar = '';

    	$RequestUri = '';

		$mycurrentitemid = $this->get_current_item_id();

		if($this->mode == 'fullscreen'){

			$navbar = '<table cellpadding="0" cellspacing="0" align="left">'."\n".

    			  '  <tr> '."\n" .

    			  '    <td>'."\n" .

    			  '      <div class="buttons">'."\n" .

    			  '        <a href="" onclick="dokeos_xajax_handler.switch_item('.$mycurrentitemid.',\'previous\');return false;" title="previous"><img border="0" src="../img/previous.gif" title="'.get_lang('ScormPrevious').'"></a>&nbsp;'."\n" .

    			  '        <a href="" onclick="dokeos_xajax_handler.switch_item('.$mycurrentitemid.',\'next\');return false;" title="next"  ><img border="0" src="../img/next.gif" title="'.get_lang('ScormNext').'"></a>&nbsp;'."\n" .

				  '        <a href="lp_controller.php?action=mode&mode=embedded" target="_top" title="embedded mode"><img border="0" src="../img/scormexitfullscreen.jpg" title="'.get_lang('ScormExitFullScreen').'"></a>'."\n" .

				  '        <a href="lp_controller.php?action=list" target="_top" title="learnpaths list"><img border="0" src="../img/exit.png" title="Exit"></a>'."\n" .

				  '      </div>'."\n" .

    			  '    </td>'."\n" .

    			  '  </tr>'."\n" .

    			  '</table>'."\n" ;

			

		}else{

			$navbar = '<table cellpadding="0" cellspacing="0" align="left">'."\n".

    			  '  <tr> '."\n" .

    			  '    <td>'."\n" .

    			  '      <div class="buttons">'."\n" .

    			  '        <a href="lp_controller.php?action=stats" target="content_name" title="stats"><img border="0" src="../img/lp_stats.gif" title="'.get_lang('ScormMystatus').'"></a>&nbsp;'."\n" .

    			  '        <a href="" onclick="dokeos_xajax_handler.switch_item('.$mycurrentitemid.',\'previous\');return false;" title="previous"><img border="0" src="../img/lp_leftarrow.gif" title="'.get_lang('ScormPrevious').'"></a>&nbsp;'."\n" .

    			  '        <a href="" onclick="dokeos_xajax_handler.switch_item('.$mycurrentitemid.',\'next\');return false;" title="next"  ><img border="0" src="../img/lp_rightarrow.gif" title="'.get_lang('ScormNext').'"></a>&nbsp;'."\n" .

				  //'        <a href="lp_controller.php?action=mode&mode=fullscreen" target="_top" title="fullscreen"><img border="0" src="../img/view_fullscreen.gif" width="18" height="18" title="'.get_lang('ScormFullScreen').'"></a>'."\n" .

				  '      </div>'."\n" .

    			  '    </td>'."\n" .

    			  '  </tr>'."\n" .

    			  '</table>'."\n" ;

		}

    	return $navbar;

    }

    /**

     * Gets the next resource in queue (url).

     * @return	string	URL to load into the viewer

     */

    function get_next_index()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_next_index()',0);}

    	//TODO

    	$index = $this->index;

    	$index ++;

    	if($this->debug>2){error_log('New LP - Now looking at ordered_items['.($index).'] - type is '.$this->items[$this->ordered_items[$index]]['type'],0);}

    	while(!empty($this->ordered_items[$index]) AND ($this->items[$this->ordered_items[$index]]->get_type() == 'dir' || $this->items[$this->ordered_items[$index]]->get_type() == 'dokeos_chapter') AND $index < $this->max_ordered_items)

    	{

    		$index ++;

    		if($index == $this->max_ordered_items)

    		{

    			return $this->index;

    		}

    	}

    	if(empty($this->ordered_items[$index])){

    		return $this->index;

    	}

    	if($this->debug>2){error_log('New LP - index is now '.$index,0);}

    	return $index;

    }

    /**

     * Gets item_id for the next element

     * @return	integer	Next item (DB) ID

     */

     function get_next_item_id()

     {

    	$new_index = $this->get_next_index();

    	return $this->ordered_items[$new_index];

     }
	/**
	 * Returns the package type ('scorm','aicc','scorm2004','dokeos','ppt'...)
	 * 
	 * Generally, the package provided is in the form of a zip file, so the function
	 * has been written to test a zip file. If not a zip, the function will return the
	 * default return value: ''
	 * @param	string	the path to the file
	 * @param	string 	the original name of the file
	 * @return	string	'scorm','aicc','scorm2004','dokeos' or '' if the package cannot be recognized
	 */
	function get_package_type($file_path,$file_name){
     	
     	//get name of the zip file without the extension
		$file_info = pathinfo($file_name);
		$filename = $file_info['basename'];//name including extension
		$extension = $file_info['extension'];//extension only
		
		if(!empty($_POST['ppt2lp']) && !in_array($extension,array('dll','exe')))
		{
			return 'ppt';
		}
		
		
		$file_base_name = str_replace('.'.$extension,'',$filename); //filename without its extension
	
		$zipFile = new pclZip($file_path);
		// Check the zip content (real size and file extension)
		$zipContentArray = $zipFile->listContent();
		$package_type='';
		$at_root = false;
		$manifest = '';

		//the following loop should be stopped as soon as we found the right imsmanifest.xml (how to recognize it?)
		foreach($zipContentArray as $thisContent)
		{
			if ( preg_match('~.(php.*|phtml)$~i', $thisContent['filename']) )
			{
				return '';
			}
			elseif(stristr($thisContent['filename'],'imsmanifest.xml')!==FALSE)
			{
				$manifest = $thisContent['filename']; //just the relative directory inside scorm/
				$package_type = 'scorm';
				break;//exit the foreach loop
			}
			elseif(preg_match('/aicc\//i',$thisContent['filename'])!==FALSE)
			{//if found an aicc directory...
				$package_type='aicc';
				//break;//don't exit the loop, because if we find an imsmanifest afterwards, we want it, not the AICC
			}
			else
			{
				$package_type = '';
			}
		}
		return $package_type;
	}
    /**

     * Gets the previous resource in queue (url). Also initialises time values for this viewing

     * @return string URL to load into the viewer

     */

    function get_previous_index()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_previous_index()',0);}

    	$index = $this->index;

    	if(isset($this->ordered_items[$index-1])){

	    	$index --;

	    	while(isset($this->ordered_items[$index]) AND ($this->items[$this->ordered_items[$index]]->get_type() == 'dir' || $this->items[$this->ordered_items[$index]]->get_type() == 'dokeos_chapter'))

	    	{

	    		$index --;

	    		if($index < 0){

	    			return $this->index;

	    		}

	    	}

    	}else{

    		if($this->debug>2){error_log('New LP - get_previous_index() - there was no previous index available, reusing '.$index,0);}

    		//no previous item

    	}

    	return $index;

    }

    /**

     * Gets item_id for the next element

     * @return	integer	Previous item (DB) ID

     */

     function get_previous_item_id()

     {

    	$new_index = $this->get_previous_index();

    	return $this->ordered_items[$new_index];

     }

    /**
     * Gets the progress value from the progress_db attribute
     * @return	integer	Current progress value
     */
    function get_progress(){
		if($this->debug>0){error_log('New LP - In learnpath::get_progress()',0);}
    	if(!empty($this->progress_db)){
    		return $this->progress_db;
    	}
    	return 0;
    }
    /**
     * Gets the progress value from the progress field in the database (allows use as abstract method)
     * @param	integer	Learnpath ID
     * @param	integer	User ID
     * @param	string	Mode of display ('%','abs' or 'both')
     * @return	integer	Current progress value as found in the database
     */
   function get_db_progress($lp_id,$user_id,$mode='%', $course_db=''){
		//if($this->debug>0){error_log('New LP - In learnpath::get_db_progress()',0);}
    	$table = Database::get_course_table('lp_view', $course_db);
    	$sql = "SELECT * FROM $table WHERE lp_id = $lp_id AND user_id = $user_id";
    	$res = api_sql_query($sql,__FILE__,__LINE__);
		$view_id = 0;
    	if(Database::num_rows($res)>0){
    		$row = Database::fetch_array($res);
    		$progress = $row['progress'];
    		$view_id = $row['id'];
    	}
    	if(!$progress){
    		$progress = '0';
    	}
    	if($mode == '%'){
    			return $progress.'%';
    	}else{
    		//get the number of items completed and the number of items total
    		$tbl = Database::get_course_table('lp_item', $course_db);
    		$sql = "SELECT count(*) FROM $tbl WHERE lp_id = ".$lp_id." 
					AND item_type NOT IN('dokeos_chapter','chapter','dir')";
    		$res = api_sql_query($sql);
    		$row = Database::fetch_array($res);
    		$total = $row[0];
    		$tbl_item_view = Database::get_course_table('lp_item_view', $course_db);
    		$tbl_item = Database::get_course_table('lp_item', $course_db);
    		
    		//$sql = "SELECT count(distinct(lp_item_id)) FROM $tbl WHERE lp_view_id = ".$view_id." AND status IN ('passed','completed','succeeded')";
    		//trying as also counting browsed and failed items
    		$sql = "SELECT count(distinct(lp_item_id)) 
					FROM $tbl_item_view as item_view
					INNER JOIN $tbl_item as item
						ON item.id = item_view.lp_item_id
						AND item_type NOT IN('dokeos_chapter','chapter','dir')
					WHERE lp_view_id = ".$view_id." 
					AND status IN ('passed','completed','succeeded','browsed','failed')";
    		$res = api_sql_query($sql, __FILE__, __LINE__);
    		$row = Database::fetch_array($res);
    		$completed = $row[0];
    		if($mode == 'abs'){
    			return $completed.'/'.$total;
    		}
    		elseif($mode == 'both')
    		{
    			if($progress<($completed/($total?$total:1))){
    				$progress = number_format(($completed/($total?$total:1))*100,0);
    			}
    			return $progress.'% ('.$completed.'/'.$total.')';
    		}
    	}
    	return $progress;
    }
    /**
     * Gets a progress bar for the learnpath by counting the number of items in it and the number of items
     * completed so far.
     * @param	string	Mode in which we want the values
     * @param	integer	Progress value to display (optional but mandatory if used in abstract context)
     * @param	string	Text to display near the progress value (optional but mandatory in abstract context)
     * @return	string	HTML string containing the progress bar
     */
    function get_progress_bar($mode='',$percentage=-1,$text_add='')
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_progress_bar()',0);}
    	if($percentage=='-1' OR $text_add==''){
    		list ($percentage, $text_add) = $this->get_progress_bar_text($mode);
    	}
    	$text = $percentage.$text_add;
    	$output = '' 
    	//.htmlentities(get_lang('ScormCompstatus'),ENT_QUOTES,'ISO-8859-1')."<br />"
	    .'<table border="0" cellpadding="0" cellspacing="0"><tr><td>'
	    .'<img id="progress_img_limit_left" src="../img/bar_1.gif" width="1" height="12">'
	    .'<img id="progress_img_full" src="../img/bar_1u.gif" width="'.$percentage.'" height="12" id="full_portion">'
	    .'<img id="progress_img_limit_middle" src="../img/bar_1m.gif" width="1" height="12">';
	    if($percentage <= 98){
	    	$output .= '<img id="progress_img_empty" src="../img/bar_1r.gif" width="'.(100-$percentage).'" height="12" id="empty_portion">';
	    }else{
	    	$output .= '<img id="progress_img_empty" src="../img/bar_1r.gif" width="0" height="12" id="empty_portion">';
	    }
	    $output .= '<img id="progress_bar_img_limit_right" src="../img/bar_1.gif" width="1" height="12"></td></tr></table>'
	    .'<div class="progresstext" id="progress_text">'.$text.'</div>';
	    return $output;
    }
    /**
     * Gets the progress bar info to display inside the progress bar. Also used by scorm_api.php
     * @param	string	Mode of display (can be '%' or 'abs').abs means we display a number of completed elements per total elements
     * //@param	integer	Additional steps to fake as completed
     * @return	list	Percentage or number and symbol (% or /xx)
     */
    function get_progress_bar_text($mode='',$add=0)
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_progress_bar_text()',0);}
    	if(empty($mode)){$mode = $this->progress_bar_mode;}
    	$total_items = $this->get_total_items_count_without_chapters();
    	if($this->debug>2){error_log('New LP - Total items available in this learnpath: '.$total_items,0);}
    	$i = $this->get_complete_items_count();
		if($this->debug>2){error_log('New LP - Items completed so far: '.$i,0);}
    	if($add != 0){
    		$i += $add;
			if($this->debug>2){error_log('New LP - Items completed so far (+modifier): '.$i,0);}
    	}
    	$text = '';
    	if($i>$total_items){
    		$i = $total_items;
    	}
    	if($mode == '%'){
	    	$percentage = ((float)$i/(float)$total_items)*100;
	    	$percentage = number_format($percentage,0);
	    	$text = '%';
    	}elseif($mode == 'abs'){
	    	$percentage = $i;
    		$text =  '/'.$total_items;
    	}
    	return array($percentage,$text);
    }
    /**
     * Gets the progress bar mode
     * @return	string	The progress bar mode attribute
     */
     function get_progress_bar_mode()
     {
		if($this->debug>0){error_log('New LP - In learnpath::get_progress_bar_mode()',0);}
     	if(!empty($this->progress_bar_mode))
    	{
    		return $this->progress_bar_mode;
    	}else{
    		return '%';
    	}
     }
    /**
     * Gets the learnpath proximity (remote or local)
     * @return	string	Learnpath proximity
     */
    function get_proximity()
	{
		if($this->debug>0){error_log('New LP - In learnpath::get_proximity()',0);}
		if(!empty($this->proximity)){return $this->proximity;}else{return '';}
	}    
    /**
     * Returns a usable array of stats related to the current learnpath and user
     * @return array	Well-formatted array containing status for the current learnpath
     */
    function get_stats()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_stats()',0);}
    	//TODO
    }
    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course (for all learnpaths and all users)
     * @param	string	Course code
     * @return array	Well-formatted array containing status for the course's learnpaths
     */
    function get_stats_course($course)
    {
		//if($this->debug>0){error_log('New LP - In learnpath::get_stats_course()',0);}
    	//TODO
    }
    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course and learnpath (for all users)
     * @param	string	Course code
     * @param	integer	Learnpath ID
     * @return array	Well-formatted array containing status for the specified learnpath
     */
    function get_stats_lp($course,$lp)
    {
		//if($this->debug>0){error_log('New LP - In learnpath::get_stats_lp()',0);}
    	//TODO
    }
    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course, learnpath and user.
     * @param	string	Course code
     * @param	integer	Learnpath ID
     * @param	integer	User ID
     * @return array	Well-formatted array containing status for the specified learnpath and user
     */
    function get_stats_lp_user($course,$lp,$user)
    {
		//if($this->debug>0){error_log('New LP - In learnpath::get_stats_lp_user()',0);}
    	//TODO
    }
    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course and learnpath (for all users)
     * @param	string	Course code
     * @param	integer	User ID
     * @return array	Well-formatted array containing status for the user's learnpaths
     */
    function get_stats_user($course,$user)
    {
		//if($this->debug>0){error_log('New LP - In learnpath::get_stats_user()',0);}
    	//TODO
    }
    /**
     * Gets the status list for all LP's items
   	 * @return	array	Array of [index] => [item ID => current status]
     */
    function get_items_status_list(){
		if($this->debug>0){error_log('New LP - In learnpath::get_items_status_list()',0);}
    	$list = array();
    	foreach($this->ordered_items as $item_id)
    	{
    		$list[]= array($item_id => $this->items[$item_id]->get_status());
    	}
    	return $list;
    }
	/**
	 * Return the number of interactions for the given learnpath Item View ID.
	 * This method can be used as static. 
	 * @param	integer	Item View ID
	 * @return	integer	Number of interactions
	 */
	function get_interactions_count_from_db($lp_iv_id=0){
		$table = Database::get_course_table('lp_iv_interaction');
		$sql = "SELECT count(*) FROM $table WHERE lp_iv_id = $lp_iv_id";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$row = Database::fetch_array($res);
		$num = $row[0];
		return $num;
	}
	/**
	 * Return the interactions as an array for the given lp_iv_id.
	 * This method can be used as static.
	 * @param	integer	Learnpath Item View ID
	 * @return	array
	 * @todo 	Translate labels 
	 */
	function get_iv_interactions_array($lp_iv_id=0){
		$list = array();
		$table = Database::get_course_table('lp_iv_interaction');
		$sql = "SELECT * FROM $table WHERE lp_iv_id = $lp_iv_id ORDER BY order_id ASC";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$num = Database::num_rows($res);
		if($num>0){
			$list[] = array(
				"order_id"=>'Order',
				"id"=>'Interaction ID',
				"type"=>'Type',
				"time"=>'Time (finished at ...)',
				"correct_responses"=>'Correct responses',
				"student_response"=>'Student response',
				"result"=>'Result',
				"latency"=>'Latency (time spent)');
			while ($row = Database::fetch_array($res)){
				$list[] = array(
					"order_id"=>($row['order_id']+1),
					"id"=>urldecode($row['interaction_id']),//urldecode because they often have %2F or stuff like that
					"type"=>$row['interaction_type'],
					"time"=>$row['completion_time'],
					//"correct_responses"=>$row['correct_responses'],
					//hide correct responses from students
					"correct_responses"=>'',
					"student_response"=>$row['student_response'],
					"result"=>$row['result'],
					"latency"=>$row['latency']);
			}
		}
		return $list;	
	}

    /**
     * Generate and return the table of contents for this learnpath. The (flat) table returned can be
     * used by get_html_toc() to be ready to display
     * @return	array	TOC as a table with 4 elements per row: title, link, status and level
     */
    function get_toc()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_toc()',0);}
    	$toc = array();
    	//echo "<pre>".print_r($this->items,true)."</pre>";
    	foreach($this->ordered_items as $item_id)
    	{
    		if($this->debug>2){error_log('New LP - learnpath::get_toc(): getting info for item '.$item_id,0);}
			//TODO change this link generation and use new function instead
    		$toc[] = array(
				'id'=>$item_id,
				'title'=>$this->items[$item_id]->get_title(),
				//'link'=>get_addedresource_link_in_learnpath('document',$item_id,1),
				'status'=>$this->items[$item_id]->get_status(),
				'level'=>$this->items[$item_id]->get_level(),
				'type' =>$this->items[$item_id]->get_type(),
				);
    	}
    	if($this->debug>2){error_log('New LP - In learnpath::get_toc() - TOC array: '.print_r($toc,true),0);}
    	return $toc;
    }
    /**
     * Gets the learning path type
     * @param	boolean		Return the name? If false, return the ID. Default is false.
     * @return	mixed		Type ID or name, depending on the parameter
     */
    function get_type($get_name = false)
    {
		$res = false;
		if($this->debug>0){error_log('New LP - In learnpath::get_type()',0);}
    	if(!empty($this->type))
    	{
    		if($get_name)
    		{
    			//get it from the lp_type table in main db
    		}else{
    			$res = $this->type;
    		}
    	}
    	if($this->debug>2){error_log('New LP - In learnpath::get_type() - Returning '.($res==false?'false':$res),0);}
		return $res;
	}

    /**
     * Gets a flat list of item IDs ordered for display (level by level ordered by order_display)
     * This method can be used as abstract and is recursive
     * @param	integer	Learnpath ID
     * @param	integer	Parent ID of the items to look for
     * @return	mixed	Ordered list of item IDs or false on error
     */
    function get_flat_ordered_items_list($lp,$parent=0){
		//if($this->debug>0){error_log('New LP - In learnpath::get_flat_ordered_items_list('.$lp.','.$parent.')',0);}
    	$list = array();
    	if(empty($lp)){return false;}
    	$tbl_lp_item = Database::get_course_table('lp_item');
    	$sql = "SELECT * FROM $tbl_lp_item WHERE lp_id = $lp AND parent_item_id = $parent ORDER BY display_order";
    	$res = api_sql_query($sql,__FILE__,__LINE__);
		while($row = Database::fetch_array($res)){
    		$sublist = learnpath::get_flat_ordered_items_list($lp,$row['id']);
    		$list[] = $row['id'];
    		foreach($sublist as $item){
    			$list[] = $item;
    		}
		}
    	return $list;
    }
    /**
     * Uses the table generated by get_toc() and returns an HTML-formatted string ready to display
     * @return	string	HTML TOC ready to display
     */
    /*function get_html_toc()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_html_toc()',0);}
    	$list = $this->get_toc();
    	//echo $this->current;
    	//$parent = $this->items[$this->current]->get_parent();
    	//if(empty($parent)){$parent = $this->ordered_items[$this->items[$this->current]->get_previous_index()];}
    	$html = '<div class="inner_lp_toc">'."\n" ;
    	//		" onchange=\"javascript:document.getElementById('toc_$parent').focus();\">\n";
		require_once('resourcelinker.inc.php');
		
		//temp variables
		$mycurrentitemid = $this->get_current_item_id();
		
    	foreach($list as $item)
    	{
    		if($this->debug>2){error_log('New LP - learnpath::get_html_toc(): using item '.$item['id'],0);}
    		//TODO complete this
    		$icon_name = array('not attempted' => 'notattempted.png',
    							'incomplete'   => 'incomplete.png',
    							'failed'       => 'failed.png',
    							'completed'    => 'completed.png',
    							'passed'	   => 'passed.png',
    							'succeeded'    => 'succeeded.png',
    							'browsed'      => 'completed.png');
    		
    		$style = 'scorm_item';
    		if($item['id'] == $this->current){
    			$style = 'scorm_item_highlight';
    		}
    		//the anchor will let us center the TOC on the currently viewed item &^D
    		$html .= '<a name="atoc_'.$item['id'].'" /><div class="'.$style.'" style="padding-left: '.($item['level']/2).'em; padding-right:'.($item['level']/2).'em" id="toc_'.$item['id'].'" >' .
    				'<img id="toc_img_'.$item['id'].'" class="scorm_status_img" src="'.$icon_name[$item['status']].'" alt="'.substr($item['status'],0,1).'" />';
    		
    		//$title = htmlspecialchars($item['title'],ENT_QUOTES,$this->encoding);
    		$title = $item['title'];
    		if(empty($title)){
    			$title = rl_get_resource_name(api_get_course_id(),$this->get_id(),$item['id']);
    			$title = htmlspecialchars($title,ENT_QUOTES,$this->encoding);
    		}
    		if(empty($title))$title = '-';
    		
    		if($item['type']!='dokeos_chapter' and $item['type']!='dir'){
					//$html .= "<a href='lp_controller.php?".api_get_cidReq()."&action=content&lp_id=".$this->get_id()."&item_id=".$item['id']."' target='lp_content_frame_name'>".$title."</a>" ;
					$url = $this->get_link('http',$item['id']);
					//$html .= '<a href="'.$url.'" target="content_name" onclick="top.load_item('.$item['id'].',\''.$url.'\');">'.$title.'</a>' ;
					//$html .= '<a href="" onclick="top.load_item('.$item['id'].',\''.$url.'\');return false;">'.$title.'</a>' ;
					$html .= '<a href="" onclick="dokeos_xajax_handler.switch_item(' .
							$mycurrentitemid.',' .
							$item['id'].');' .
							'return false;" >'.$title.'</a>' ;
    		}else{
    				$html .= $title;
    		}
    		$html .= "</div>\n";
    	}
    	$html .= "</div>\n";
    	return $html;
    }*/
    
    
    /**
     * Uses the table generated by get_toc() and returns an HTML-formatted string ready to display
     * @return	string	HTML TOC ready to display
     */
    function get_html_toc()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_html_toc()',0);}
    	$list = $this->get_toc();
    	
    	
    	
    	//echo $this->current;
    	//$parent = $this->items[$this->current]->get_parent();
    	//if(empty($parent)){$parent = $this->ordered_items[$this->items[$this->current]->get_previous_index()];}
    	$html .= '<div class="inner_lp_toc">'."\n" ;
    	if($_SESSION["is_courseAdmin"]==1){
    		$html.="<a href='lp_controller.php?cidReq=".$_SESSION['_cid']."&action=build&lp_id=".$this->lp_id."' style='font-size: 11px' target='_parent'>".mb_convert_encoding(get_lang("Build"),$this->encoding)."</a>&nbsp;<a style='font-size: 11px' href='lp_controller.php?cidReq=".$_SESSION['_cid']."&action=admin_view&lp_id=".$this->lp_id."' target='_parent'>".mb_convert_encoding(get_lang("BasicOverview"),$this->encoding)."</a>&nbsp;".mb_convert_encoding(get_lang("Display"),$this->encoding)."<br><br>";
    	}
    	//		" onchange=\"javascript:document.getElementById('toc_$parent').focus();\">\n";
		require_once('resourcelinker.inc.php');
		
		//temp variables
		$mycurrentitemid = $this->get_current_item_id();
		
    	foreach($list as $item)
    	{
    		if($this->debug>2){error_log('New LP - learnpath::get_html_toc(): using item '.$item['id'],0);}
    		//TODO complete this
    		$icon_name = array('not attempted' => 'notattempted.png',
    							'incomplete'   => 'incomplete.png',
    							'failed'       => 'failed.png',
    							'completed'    => 'completed.png',
    							'passed'	   => 'passed.png',
    							'succeeded'    => 'succeeded.png',
    							'browsed'      => 'completed.png');
    		
    		$style = 'scorm_item';
    		if($item['id'] == $this->current){
    			$style = 'scorm_item_highlight';
    		}
    		//the anchor will let us center the TOC on the currently viewed item &^D
    		
    		if($item['type']!='dokeos_module' AND $item['type']!='dokeos_chapter'){
    		
	    		$html .= '<a name="atoc_'.$item['id'].'" /><div class="'.$style.'" style="padding-left: '.($item['level']*1.5).'em; padding-right:'.($item['level']/2).'em" id="toc_'.$item['id'].'" >' .
	    				'';
    		
    		}
    		
    		else{
    			
    			$html .= '<div class="'.$style.'" style="padding-left: '.($item['level']*2).'em; padding-right:'.($item['level']*1.5).'em" id="toc_'.$item['id'].'" >' .
	    				'';
    			
    		}
    		
    		//$title = htmlspecialchars($item['title'],ENT_QUOTES,$this->encoding);
    		$title = $item['title'];
    		if(empty($title)){
    			$title = rl_get_resource_name(api_get_course_id(),$this->get_id(),$item['id']);
    			$title = htmlspecialchars($title,ENT_QUOTES,$this->encoding);
    		}
    		if(empty($title))$title = '-';
    		
    		if($item['type']!='dokeos_chapter' and $item['type']!='dir' AND $item['type']!='dokeos_module'){
					//$html .= "<a href='lp_controller.php?".api_get_cidReq()."&action=content&lp_id=".$this->get_id()."&item_id=".$item['id']."' target='lp_content_frame_name'>".$title."</a>" ;
					$url = $this->get_link('http',$item['id']);
					//$html .= '<a href="'.$url.'" target="content_name" onclick="top.load_item('.$item['id'].',\''.$url.'\');">'.$title.'</a>' ;
					//$html .= '<a href="" onclick="top.load_item('.$item['id'].',\''.$url.'\');return false;">'.$title.'</a>' ;
					
					
					$html .= '<a href="" onclick="dokeos_xajax_handler.switch_item(' .
							$mycurrentitemid.',' .
							$item['id'].');' .
							'return false;" ><img align="absbottom" width="13" height="13" src="../img/lp_document.png">&nbsp;'.stripslashes($title).'</a>' ;
					
					
    		}
    		elseif($item['type']=='dokeos_module' || $item['type']=='dokeos_chapter'){
    				$html .= "<img align='absbottom' width='13' height='13' src='../img/lp_dokeos_module.png'>&nbsp;".stripslashes($title);
    		}
    		
    		elseif($item['type']=='dir'){
    				$html .= stripslashes($title);
    		}
    		$html .= "<img id='toc_img_".$item['id']."' src='".$icon_name[$item['status']]."' alt='".substr($item['status'],0,1)."' /></div>\n";
    	}
    	$html .= "</div>\n";
    	return $html;
    }
    /**
     * Gets the learnpath maker name - generally the editor's name
     * @return	string	Learnpath maker name
     */
    function get_maker()
	{
		if($this->debug>0){error_log('New LP - In learnpath::get_maker()',0);}
		if(!empty($this->maker)){return $this->maker;}else{return '';}
	}    
    /**
     * Gets the user-friendly message stored in $this->message
     * @return	string	Message
     */
    function get_message(){

		if($this->debug>0){error_log('New LP - In learnpath::get_message()',0);}
    	return $this->message;
    }
    /**
     * Gets the learnpath name/title
     * @return	string	Learnpath name/title
     */
    function get_name()
	{
		if($this->debug>0){error_log('New LP - In learnpath::get_name()',0);}
		if(!empty($this->name)){return $this->name;}else{return 'N/A';}
	}
    /**
     * Gets a link to the resource from the present location, depending on item ID.
     * @param	string	Type of link expected
     * @param	integer	Learnpath item ID
     * @return	string	Link to the lp_item resource
     */
    function get_link($type='http',$item_id=null)
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_link('.$type.','.$item_id.')',0);}
    	if(empty($item_id))
    	{
    		if($this->debug>2){error_log('New LP - In learnpath::get_link() - no item id given in learnpath::get_link(), using current: '.$this->get_current_item_id(),0);}
    		$item_id = $this->get_current_item_id();
    	}

    	if(empty($item_id)){
    		if($this->debug>2){error_log('New LP - In learnpath::get_link() - no current item id found in learnpath object',0);}
    		//still empty, this means there was no item_id given and we are not in an object context or
    		//the object property is empty, return empty link
    		$item_id = $this->first();
    		return '';
    	}

    	$file = '';
		$lp_db = Database::get_current_course_database();
		$lp_pref = Database::get_course_table_prefix();
		$lp_table = $lp_db.'.'.$lp_pref.'lp';
		$lp_item_table = $lp_db.'.'.$lp_pref.'lp_item';
    	$sel = "SELECT l.lp_type as ltype, l.path as lpath, li.item_type as litype, li.path as lipath, li.parameters as liparams " .
    			"FROM $lp_table l, $lp_item_table li WHERE li.id = $item_id AND li.lp_id = l.id";
    	if($this->debug>2){error_log('New LP - In learnpath::get_link() - selecting item '.$sel,0);}
    	$res = api_sql_query($sel);
    	if(Database::num_rows($res)>0)
    	{
    		$row = Database::fetch_array($res);
    		//var_dump($row);
    		$lp_type = $row['ltype'];
    		$lp_path = $row['lpath'];
    		$lp_item_type = $row['litype'];
    		$lp_item_path = $row['lipath'];
    		$lp_item_params = $row['liparams'];
    		//add ? if none - left commented to give freedom to scorm implementation
    		//if(substr($lp_item_params,0,1)!='?'){
    		//	$lp_item_params = '?'.$lp_item_params;
    		//}
    		$sys_course_path = api_get_path(SYS_COURSE_PATH).api_get_course_path();
    		if($type == 'http'){
    			$course_path = api_get_path(WEB_COURSE_PATH).api_get_course_path(); //web path
    		}else{
    			$course_path = $sys_course_path; //system path
    		}
    		//now go through the specific cases to get the end of the path
    		switch($lp_type){
    			case 1:
    				if($lp_item_type == 'dokeos_chapter'){
    					$file = 'lp_content.php?type=dir';
    				}else{
	    				require_once('resourcelinker.inc.php');
    					$file = rl_get_resource_link_for_learnpath(api_get_course_id(),$this->get_id(),$item_id);
    					$tmp_array=explode("/",$file);
    					$document_name=$tmp_array[count($tmp_array)-1];
    					if(strpos($document_name,'_DELETED_')){
    						$file = 'blank.php?error=document_deleted';
    					}
    				}
    				break;
    			case 2:
	   				if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - Item type: '.$lp_item_type,0);}
    				if($lp_item_type!='dir'){
    					//Quite complex here:
    					//we want to make sure 'http://' (and similar) links can 
    					//be loaded as is (withouth the Dokeos path in front) but
    					//some contents use this form: resource.htm?resource=http://blablabla
    					//which means we have to find a protocol at the path's start, otherwise
    					//it should not be considered as an external URL
    					
    					//if($this->prerequisites_match($item_id)){
		    				if(preg_match('#^[a-zA-Z]{2,5}://#',$lp_item_path)!=0){
		    					if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - Found match for protocol in '.$lp_item_path,0);}
		    					//distant url, return as is
		    					$file = $lp_item_path;
		    				}else{
		    					if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - No starting protocol in '.$lp_item_path,0);}
		    					//prevent getting untranslatable urls
		    					$lp_item_path = preg_replace('/%2F/','/',$lp_item_path);
		    					$lp_item_path = preg_replace('/%3A/',':',$lp_item_path);
			    				//prepare the path
			    				$file = $course_path.'/scorm/'.$lp_path.'/'.$lp_item_path;
			    				//TODO fix this for urls with protocol header
			    				$file = str_replace('//','/',$file);
			    				$file = str_replace(':/','://',$file);

			    				if(!is_file($sys_course_path.'/scorm/'.$lp_path.'/'.$lp_item_path))
			    				{//if file not found
			    					$decoded = html_entity_decode($lp_item_path);
			    					if(!is_file($sys_course_path.'/scorm/'.$lp_path.'/'.$decoded))
			    					{
			    						require_once('resourcelinker.inc.php');
    					$file = rl_get_resource_link_for_learnpath(api_get_course_id(),$this->get_id(),$item_id);
    					$tmp_array=explode("/",$file);
    					$document_name=$tmp_array[count($tmp_array)-1];
    					if(strpos($document_name,'_DELETED_')){
    						$file = 'blank.php?error=document_deleted';
    					}

			    					}
			    					else
			    					{
			    						$file = $course_path.'/scorm/'.$lp_path.'/'.$decoded;
			    					}
			    				}
		    				}
    					//}else{
    						//prerequisites did not match
    						//$file = 'blank.php';
    					//}
    					//We want to use parameters if they were defined in the imsmanifest
    					if($file!='blank.php')
    					{
    						$file.= $lp_item_params;
    					}
    				}else{
    					$file = 'lp_content.php?type=dir';
    				}
    				break;
    			case 3:
    				if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - Item type: '.$lp_item_type,0);}
    				//formatting AICC HACP append URL
    				$aicc_append = '?aicc_sid='.urlencode(session_id()).'&aicc_url='.urlencode(api_get_path(WEB_CODE_PATH).'newscorm/aicc_hacp.php').'&';
    				if($lp_item_type!='dir'){
    					//Quite complex here:
    					//we want to make sure 'http://' (and similar) links can 
    					//be loaded as is (withouth the Dokeos path in front) but
    					//some contents use this form: resource.htm?resource=http://blablabla
    					//which means we have to find a protocol at the path's start, otherwise
    					//it should not be considered as an external URL
    					
	    				if(preg_match('#^[a-zA-Z]{2,5}://#',$lp_item_path)!=0){
	    					if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - Found match for protocol in '.$lp_item_path,0);}
	    					//distant url, return as is
	    					$file = $lp_item_path;
	    					/*
	    					if(stristr($file,'<servername>')!==false){
	    						$file = str_replace('<servername>',$course_path.'/scorm/'.$lp_path.'/',$lp_item_path);
	    					}
	    					*/
	    					$file .= $aicc_append;
	    				}else{
	    					if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - No starting protocol in '.$lp_item_path,0);}
	    					//prevent getting untranslatable urls
	    					$lp_item_path = preg_replace('/%2F/','/',$lp_item_path);
	    					$lp_item_path = preg_replace('/%3A/',':',$lp_item_path);
		    				//prepare the path - lp_path might be unusable because it includes the "aicc" subdir name
		    				$file = $course_path.'/scorm/'.$lp_path.'/'.$lp_item_path;
		    				//TODO fix this for urls with protocol header
		    				$file = str_replace('//','/',$file);
		    				$file = str_replace(':/','://',$file);
		    				$file .= $aicc_append;
	    				}
    				}else{
    					$file = 'lp_content.php?type=dir';
    				}

    				break;

    			case 4:

    				break;

    			default:

    				break;	

    		}

    	}

    	if($this->debug>2){error_log('New LP - In learnpath::get_link() - returning "'.$file.'" from get_link',0);}

    	return $file;

    }

    /**

     * Gets the latest usable view or generate a new one

     * @param	integer	Optional attempt number. If none given, takes the highest from the lp_view table

     * @return	integer	DB lp_view id

     */

    function get_view($attempt_num=0)

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_view()',0);}

    	$search = '';

    	//use $attempt_num to enable multi-views management (disabled so far)

    	if($attempt_num != 0 AND intval(strval($attempt_num)) == $attempt_num)

    	{

    		$search = 'AND view_count = '.$attempt_num;

    	}

    	//when missing $attempt_num, search for a unique lp_view record for this lp and user

    	$lp_view_table = Database::get_course_table('lp_view');

    	$sql = "SELECT id, view_count FROM $lp_view_table " .

    			"WHERE lp_id = ".$this->get_id()." " .

    			"AND user_id = ".$this->get_user_id()." " .

    			$search .

    			" ORDER BY view_count DESC";

    	$res = api_sql_query($sql);

    	if(Database::num_rows($res)>0)

    	{

    		$row = Database::fetch_array($res);

    		$this->lp_view_id = $row['id'];

    	}else{

    		//no database record, create one

    		$sql = "INSERT INTO $lp_view_table(lp_id,user_id,view_count)" .

    				"VALUES (".$this->get_id().",".$this->get_user_id().",1)";

    		$res = api_sql_query($sql);

    		$id = Database::get_last_insert_id();

    		$this->lp_view_id = $id;

    	}

    }

    /**

     * Gets the current view id

     * @return	integer	View ID (from lp_view)

     */

    function get_view_id()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_view_id()',0);}

       	if(!empty($this->lp_view_id))

    	{

    		return $this->lp_view_id;

    	}else{

    		return 0;

    	}

    }

    /**

     * Gets the update queue

     * @return	array	Array containing IDs of items to be updated by JavaScript

     */

    function get_update_queue()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_update_queue()',0);}

    	return $this->update_queue;

    }

    /**

     * Gets the user ID

     * @return	integer	User ID

     */

    function get_user_id()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_user_id()',0);}

    	if(!empty($this->user_id))

    	{

    		return $this->user_id;

    	}else{

    		return false;

    	}

    }

    /**

     * Logs a message into a file

     * @param	string 	Message to log

     * @return	boolean	True on success, false on error or if msg empty

     */

    function log($msg)

    {

		if($this->debug>0){error_log('New LP - In learnpath::log()',0);}

    	//TODO

    	$this->error .= $msg."\n";

    	return true;

    }

    /**

     * Moves an item up and down at its level

     * @param	integer	Item to move up and down

     * @param	string	Direction 'up' or 'down'

     * @return	integer	New display order, or false on error

     */

    function move_item($id, $direction){

		if($this->debug>0){error_log('New LP - In learnpath::move_item('.$id.','.$direction.')',0);}

    	if(empty($id) or empty($direction)){return false;}

    	$tbl_lp_item = Database::get_course_table('lp_item');

		$sql_sel = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE id = " . $id;
    	$res_sel = api_sql_query($sql_sel,__FILE__,__LINE__);

    	//check if elem exists

    	if(Database::num_rows($res_sel)<1){return false;}

    	//gather data

    	$row = Database::fetch_array($res_sel);

    	$previous = $row['previous_item_id'];

    	$next = $row['next_item_id'];

    	$display = $row['display_order'];

    	$parent = $row['parent_item_id'];

    	$lp = $row['lp_id'];

    	//update the item (switch with previous/next one)

    	switch($direction)
		{
    		case 'up':
    			if($this->debug>2){error_log('Movement up detected',0);}

    			if($display <= 1){/*do nothing*/}

    			else{

			     	$sql_sel2 = "SELECT * 
						FROM $tbl_lp_item 
						WHERE id = $previous";

					if($this->debug>2){error_log('Selecting previous: '.$sql_sel2,0);}

			    	$res_sel2 = api_sql_query($sql_sel2,__FILE__,__LINE__);

			    	if(Database::num_rows($res_sel2)<1){$previous_previous = 0;}

			    	//gather data

			    	$row2 = Database::fetch_array($res_sel2);

			    	$previous_previous = $row2['previous_item_id'];

			 		//update previous_previous item (switch "next" with current)
			
			 		if($previous_previous != 0){

				    	$sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $id WHERE id = $previous_previous";
						
				    	if($this->debug>2){error_log($sql_upd2,0);}

				    	$res_upd2 = api_sql_query($sql_upd2);

			 		}

				 	//update previous item (switch with current)

			    	if($previous != 0){

				    	$sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $next, previous_item_id = $id, display_order = display_order +1 WHERE id = $previous";

				    	if($this->debug>2){error_log($sql_upd2,0);}

				    	$res_upd2 = api_sql_query($sql_upd2);

			    	}

			    	//update current item (switch with previous)

			    	if($id != 0){

				    	$sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $previous, previous_item_id = $previous_previous, display_order = display_order-1 WHERE id = $id";

				    	if($this->debug>2){error_log($sql_upd2,0);}

				    	$res_upd2 = api_sql_query($sql_upd2);

			    	}

			    	//update next item (new previous item)

			    	if($next != 0){

				    	$sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $previous WHERE id = $next";

				    	if($this->debug>2){error_log($sql_upd2,0);}

				    	$res_upd2 = api_sql_query($sql_upd2);

			    	}

			    	$display = $display-1;    				

    			}

    			break;

    		case 'down':

    			if($this->debug>2){error_log('Movement down detected',0);}

    			if($next == 0){/*do nothing*/}

    			else{

			     	$sql_sel2 = "SELECT * FROM $tbl_lp_item WHERE id = $next";

					if($this->debug>2){error_log('Selecting next: '.$sql_sel2,0);}

			    	$res_sel2 = api_sql_query($sql_sel2,__FILE__,__LINE__);

			    	if(Database::num_rows($res_sel2)<1){$next_next = 0;}

			    	//gather data

			    	$row2 = Database::fetch_array($res_sel2);

			    	$next_next = $row2['next_item_id'];

    				//update previous item (switch with current)

					if($previous != 0){

				    	$sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $next WHERE id = $previous";

				    	$res_upd2 = api_sql_query($sql_upd2);

					}

				    //update current item (switch with previous)

			    	if($id != 0){

				    	$sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $next, next_item_id = $next_next, display_order = display_order+1 WHERE id = $id";

				    	$res_upd2 = api_sql_query($sql_upd2);

			    	}

			    	//update next item (new previous item)

			    	if($next != 0){

				    	$sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $previous, next_item_id = $id, display_order = display_order-1 WHERE id = $next";

				    	$res_upd2 = api_sql_query($sql_upd2);    				

			    	}

			    	//update next_next item (switch "previous" with current)

			    	if($next_next != 0){

				    	$sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $id WHERE id = $next_next";

				    	$res_upd2 = api_sql_query($sql_upd2);    				

			    	}

			    	$display = $display+1;    				

    			}

    			break;

    		default:

    			return false;

    	}

		return $display;

    }

    /**

     * Updates learnpath attributes to point to the next element

     * The last part is similar to set_current_item but processing the other way around

     */

    function next(){

		if($this->debug>0){error_log('New LP - In learnpath::next()',0);}

    	$this->last = $this->get_current_item_id();

    	$this->items[$this->last]->save(false);

    	$this->autocomplete_parents($this->last);

    	$new_index = $this->get_next_index();

    	if($this->debug>2){error_log('New LP - New index: '.$new_index,0);}

    	$this->index = $new_index;

    	if($this->debug>2){error_log('New LP - Now having orderedlist['.$new_index.'] = '. $this->ordered_items[$new_index],0);}

    	$this->current = $this->ordered_items[$new_index];

    	if($this->debug>2){error_log('New LP - new item id is '.$this->current.'-'.$this->get_current_item_id(),0);}

    }

    /**

     * Open a resource = initialise all local variables relative to this resource. Depending on the child

     * class, this might be redefined to allow several behaviours depending on the document type.

     * @param integer Resource ID

     * @return boolean True on success, false otherwise

     */

    function open($id)

    {

		if($this->debug>0){error_log('New LP - In learnpath::open()',0);}

    	//TODO

    	//set the current resource attribute to this resource

    	//switch on element type (redefine in child class?)

    	//set status for this item to "opened"

    	//start timer

    	//initialise score

    	$this->index = 0; //or = the last item seen (see $this->last)

    }

    /**

     * Check that all prerequisites are fulfilled. Returns true and an empty string on succes, returns false

     * and the prerequisite string on error.

     * This function is based on the rules for aicc_script language as described in the SCORM 1.2 CAM documentation page 108.

     * @param	integer	Optional item ID. If none given, uses the current open item.

     * @return	boolean	True if prerequisites are matched, false otherwise

     * @return	string	Empty string if true returned, prerequisites string otherwise.

     */

    function prerequisites_match($item = null){

		if($this->debug>0){error_log('New LP - In learnpath::prerequisites_match()',0);}

    	if(empty($item)){$item = $this->current;}

    	if(is_object($this->items[$item])){

	    	$prereq_string = $this->items[$item]->get_prereq_string();

	    	if(empty($prereq_string)){return true;}

	    	//clean spaces

	    	$prereq_string = str_replace(' ','',$prereq_string);

	    	if($this->debug>0){error_log('Found prereq_string: '.$prereq_string,0);}

	    	//now send to the parse_prereq() function that will check this component's prerequisites
			
	    	$result = $this->items[$item]->parse_prereq($prereq_string,$this);

	    	if($result === false){

	    		$this->set_error_msg($this->items[$item]->prereq_alert);

	    	}

    	}else{

    		$result = true;

    		if($this->debug>1){error_log('New LP - $this->items['.$item.'] was not an object',0);}

    	}

    	if($this->debug>1){error_log('New LP - End of prerequisites_match(). Error message is now '.$this->error,0);}

    	return $result;

    }

    /**

     * Updates learnpath attributes to point to the previous element

     * The last part is similar to set_current_item but processing the other way around

     */

    function previous(){

		if($this->debug>0){error_log('New LP - In learnpath::previous()',0);}

    	$this->last = $this->get_current_item_id();

    	$this->items[$this->last]->save(false);

    	$this->autocomplete_parents($this->last);

    	$new_index = $this->get_previous_index();

    	$this->index = $new_index;

    	$this->current = $this->ordered_items[$new_index];

    }    

    /**

     * Publishes a learnpath. This basically means show or hide the learnpath 

     * to normal users.

     * Can be used as abstract

	 * @param	integer	Learnpath ID

     * @param	string	New visibility

     */

    function toggle_visibility($lp_id,$set_visibility='v')

    {

		//if($this->debug>0){error_log('New LP - In learnpath::toggle_visibility()',0);}

    	$tbl_lp = Database::get_course_table('lp');

		$sql="SELECT * FROM $tbl_lp where id=$lp_id";

		$result=api_sql_query($sql,__FILE__,__LINE__);

		$row=Database::fetch_array($result);

		$name=domesticate($row['name']);

		if($set_visibility == 'i') { 

			$s=$name." ".get_lang('_no_published'); 

			$dialogBox=$s; 

			$v=0; 

		}

		if($set_visibility == 'v') { 

			$s=$name." ".get_lang('_published');    

			$dialogBox=$s; 

			$v=1; 

		}

		$tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);

		$link = 'newscorm/lp_controller.php?action=view&lp_id='.$lp_id;

		$sql="SELECT * FROM $tbl_tool where name='$name' and image='scormbuilder.gif' and link LIKE '$link%'";

		$result=api_sql_query($sql,__FILE__,__LINE__);

		$num=Database::num_rows($result);

		$row2=Database::fetch_array($result);
		
		//if($this->debug>2){error_log('New LP - '.$sql.' - '.$num,0);}

		if(($set_visibility == 'i') && ($num>0))

		{

			//it is visible or hidden but once was published

			if(($row2['visibility'])==1)

			{

				$sql ="DELETE FROM $tbl_tool WHERE (name='$name' and image='scormbuilder.gif' and link LIKE '$link%')";

			}

			else

			{

				$sql ="UPDATE $tbl_tool set visibility=1 WHERE (name='$name' and image='scormbuilder.gif' and link LIKE '$link%')";

			}

		}

		elseif(($set_visibility == 'v') && ($num==0))

		{

			$sql ="INSERT INTO $tbl_tool (name, link, image, visibility, admin, address, added_tool) VALUES ('$name','newscorm/lp_controller.php?action=view&lp_id=$lp_id','scormbuilder.gif','$v','0','pastillegris.gif',0)";

		}

		else

		{

			//parameter and database incompatible, do nothing

		}

		$result=api_sql_query($sql,__FILE__,__LINE__);

		//if($this->debug>2){error_log('New LP - Leaving learnpath::toggle_visibility: '.$sql,0);}

	}
    /**
     * Restart the whole learnpath. Return the URL of the first element.
     * Make sure the results are saved with anoter method. This method should probably be
     * redefined in children classes.
     * @return string URL to load in the viewer
     */
    function restart()
    {
		if($this->debug>0){error_log('New LP - In learnpath::restart()',0);}
    	//TODO
    	//call autosave method to save the current progress
    	//$this->index = 0;
     	$lp_view_table = Database::get_course_table('lp_view');
   		$sql = "INSERT INTO $lp_view_table (lp_id, user_id, view_count) " .
   				"VALUES (".$this->lp_id.",".$this->get_user_id().",".($this->attempt+1).")";
   		if($this->debug>2){error_log('New LP - Inserting new lp_view for restart: '.$sql,0);}
  		$res = api_sql_query($sql);     	
    	if($view_id = Database::get_last_insert_id($res))
    	{
     		$this->lp_view_id = $view_id;
     		$this->attempt = $this->attempt+1;
     	}else{
     		$this->error = 'Could not insert into item_view table...';
     		return false;
     	}
     	$this->autocomplete_parents($this->current);
     	foreach($this->items as $index=>$dummy){
     		$this->items[$index]->restart();
     		$this->items[$index]->set_lp_view($this->lp_view_id);
     	}
     	$this->first();
     	return true;
    }
    /**
     * Saves the current item
     * @return	boolean
     */
    function save_current(){
		if($this->debug>0){error_log('New LP - In learnpath::save_current()',0);}
    	//TODO do a better check on the index pointing to the right item (it is supposed to be working
    	// on $ordered_items[] but not sure it's always safe to use with $items[])
    	if($this->debug>2){error_log('New LP - save_current() saving item '.$this->current,0);}
    	if($this->debug>2){error_log(''.print_r($this->items,true),0);}
    	if(is_object($this->items[$this->current])){
    		$res = $this->items[$this->current]->save(false);
    		$this->autocomplete_parents($this->current);
    		$status = $this->items[$this->current]->get_status();
    		$this->append_message('new_item_status: '.$status);
     		$this->update_queue[$this->current] = $status;
    		return $res;
    	}
    	return false;
    }
    /**
     * Saves the given item
     * @param	integer	Item ID. Optional (will take from $_REQUEST if null)
     * @param	boolean	Save from url params (true) or from current attributes (false). Optional. Defaults to true
     * @return	boolean
     */
    function save_item($item_id=null,$from_outside=true){
		if($this->debug>0){error_log('New LP - In learnpath::save_item('.$item_id.','.$from_outside.')',0);}
    	//TODO do a better check on the index pointing to the right item (it is supposed to be working
    	// on $ordered_items[] but not sure it's always safe to use with $items[])
    	if(empty($item_id)){
    		$item_id = $this->escape_string($_REQUEST['id']);
    	}
    	if(empty($item_id))
    	{
    		$item_id = $this->get_current_item_id();
    	}
    	if($this->debug>2){error_log('New LP - save_current() saving item '.$item_id,0);}
    	if(is_object($this->items[$item_id])){
    		$res = $this->items[$item_id]->save($from_outside);
    		$this->autocomplete_parents($item_id);
    		$status = $this->items[$item_id]->get_status();
    		$this->append_message('new_item_status: '.$status);
    		$this->update_queue[$item_id] = $status;
    		return $res;
    	}
    	return false;
    }
    /**
     * Saves the last item seen's ID only in case 
     */
    function save_last(){

		if($this->debug>0){error_log('New LP - In learnpath::save_last()',0);}

   		$table = Database::get_course_table('lp_view');

    	if(isset($this->current)){

    		if($this->debug>2){error_log('New LP - Saving current item ('.$this->current.') for later review',0);}

    		$sql = "UPDATE $table SET last_item = ".$this->get_current_item_id()." " .

    				"WHERE lp_id = ".$this->get_id()." AND user_id = ".$this->get_user_id();

    		if($this->debug>2){error_log('New LP - Saving last item seen : '.$sql,0);}

			$res = api_sql_query($sql,__FILE__,__LINE__);

    	}

    	//save progress

    	list($progress,$text) = $this->get_progress_bar_text('%');

    	if($progress>=0 AND $progress<=100){

    		$progress= (int)$progress;

    		$sql = "UPDATE $table SET progress = $progress " .

    				"WHERE lp_id = ".$this->get_id()." AND " .

    						"user_id = ".$this->get_user_id();

    		$res = @mysql_query($sql); //ignore errors as some tables might not have the progress field just yet

    		$this->progress_db = $progress;

    	}

    }
    /**
     * Sets the current item ID (checks if valid and authorized first)
     * @param	integer	New item ID. If not given or not authorized, defaults to current
     */
    function set_current_item($item_id=null)
    {
		if($this->debug>0){error_log('New LP - In learnpath::set_current_item('.$item_id.')',0);}
    	if(empty($item_id)){
    		if($this->debug>2){error_log('New LP - No new current item given, ignore...',0);}
    		//do nothing
    	}else{
   			if($this->debug>2){error_log('New LP - New current item given is '.$item_id.'...',0);}
    		$item_id = $this->escape_string($item_id);
    		//TODO check in database here
    		$this->last = $this->current;
    		$this->current = $item_id;
    		//TODO update $this->index as well
    		foreach($this->ordered_items as $index => $item)
    		{
    			if($item == $this->current)
    			{
    				$this->index = $index;
	   				break;
    			}
    		}
	    	if($this->debug>2){error_log('New LP - set_current_item('.$item_id.') done. Index is now : '.$this->index,0);}
    	}
    }
    /**
     * Sets the encoding
     * @param	string	New encoding
     */
    function set_encoding($enc='ISO-8859-1'){
		if($this->debug>0){error_log('New LP - In learnpath::set_encoding()',0);}
    	$enc = strtoupper($enc);
	 	$encodings = array('UTF-8','ISO-8859-1','ISO-8859-15','SHIFT-JIS');
		if(in_array($enc,$encodings)){
		 	$lp = $this->get_id();
		 	if($lp!=0){
		 		$tbl_lp = Database::get_course_table('lp');
		 		$sql = "UPDATE $tbl_lp SET default_encoding = '$enc' WHERE id = ".$lp;
		 		$res = api_sql_query($sql);
		 		return $res;
		 	}
		}
		return false;
    }
	/**
	 * Sets the JS lib setting in the database directly. 
	 * This is the JavaScript library file this lp needs to load on startup
	 * @param	string	Proximity setting
	 */
	 function set_jslib($lib=''){
		if($this->debug>0){error_log('New LP - In learnpath::set_jslib()',0);}
	 	$lp = $this->get_id();
	 	if($lp!=0){
	 		$tbl_lp = Database::get_course_table('lp');
	 		$sql = "UPDATE $tbl_lp SET js_lib = '$lib' WHERE id = ".$lp;
	 		$res = api_sql_query($sql);
	 		return $res;
	 	}else{
	 		return false;
	 	}
	 }
    /**
     * Sets the name of the LP maker (publisher) (and save)
     * @param	string	Optional string giving the new content_maker of this learnpath
     */
    function set_maker($name=''){

		if($this->debug>0){error_log('New LP - In learnpath::set_maker()',0);}

    	if(empty($name))return false;

    	

    	$this->maker = $this->escape_string($name);

		$lp_table = Database::get_course_table('lp');

		$lp_id = $this->get_id();

		$sql = "UPDATE $lp_table SET content_maker = '".$this->maker."' WHERE id = '$lp_id'";

		if($this->debug>2){error_log('New LP - lp updated with new content_maker : '.$this->maker,0);}

		//$res = Database::query($sql);

		$res = api_sql_query($sql);

    	return true;

    }    

    /**

     * Sets the name of the current learnpath (and save)

     * @param	string	Optional string giving the new name of this learnpath

     */

    function set_name($name=''){

		if($this->debug>0){error_log('New LP - In learnpath::set_name()',0);}

    	if(empty($name))return false;

    	

    	$this->name = $this->escape_string($name);

		$lp_table = Database::get_course_table('lp');

		$lp_id = $this->get_id();

		$sql = "UPDATE $lp_table SET name = '".$this->name."' WHERE id = '$lp_id'";

		if($this->debug>2){error_log('New LP - lp updated with new name : '.$this->name,0);}

		//$res = Database::query($sql);

		$res = api_sql_query($sql, __FILE__, __LINE__);
		
		// if the lp is visible on the homepage, change his name there
		if(mysql_affected_rows())
		{ 
			$table = Database :: get_course_table(TABLE_TOOL_LIST);
			$sql = 'UPDATE '.$table.' SET
						name = "'.$this->name.'"
					WHERE link = "newscorm/lp_controller.php?action=view&lp_id='.$lp_id.'"';
			api_sql_query($sql, __FILE__, __LINE__);
		}

    	return true;

    }

    /**
     * Sets the location/proximity of the LP (local/remote) (and save)
     * @param	string	Optional string giving the new location of this learnpath
     */
    function set_proximity($name=''){
		if($this->debug>0){error_log('New LP - In learnpath::set_proximity()',0);}
    	if(empty($name))return false;
    	
    	$this->proximity = $this->escape_string($name);
		$lp_table = Database::get_course_table('lp');
		$lp_id = $this->get_id();
		$sql = "UPDATE $lp_table SET content_local = '".$this->proximity."' WHERE id = '$lp_id'";
		if($this->debug>2){error_log('New LP - lp updated with new proximity : '.$this->proximity,0);}
		//$res = Database::query($sql);
		$res = api_sql_query($sql);
    	return true;
    }    
    /**
     * Sets the previous item ID to a given ID. Generally, this should be set to the previous 'current' item
     * @param	integer	DB ID of the item
     */
    function set_previous_item($id)
    {
		if($this->debug>0){error_log('New LP - In learnpath::set_previous_item()',0);}
    	$this->last = $id;
    }
    /**
     * Sets the object's error message
     * @param	string	Error message. If empty, reinits the error string
     * @return 	void
     */
    function set_error_msg($error='')
    {
		if($this->debug>0){error_log('New LP - In learnpath::set_error_msg()',0);}
    	if(empty($error)){
    		$this->error = '';
    	}else{
    		$this->error .= $error;
    	}
    }
    /**
     * Launches the current item if not 'sco' (starts timer and make sure there is a record ready in the DB)
     * 
     */
    function start_current_item(){
		if($this->debug>0){error_log('New LP - In learnpath::start_current_item()',0);}
    	if($this->current != 0 AND
    		is_object($this->items[$this->current]))
    	{
    		$type = $this->get_type();
			if(
				($type == 2 && $this->items[$this->current]->get_type()!='sco')
				OR
				($type == 3 && $this->items[$this->current]->get_type()!='au')
				OR
				($type==1)
			)
			{
	    		$this->items[$this->current]->open();
	    		    		
	    		$this->autocomplete_parents($this->current);
	    		$prereq_check = $this->prerequisites_match($this->current);
	    		if($prereq_check === true) //launch the prerequisites check and set error if needed
	    		{
	    			$this->items[$this->current]->save(false);
	    		}
	    		//$this->update_queue[$this->last] = $this->items[$this->last]->get_status();
			}else{
				//if sco, then it is supposed to have been updated by some other call
			}
    	}
    	return true;
    }

    /**

     * Stops the processing and counters for the old item (as held in $this->last)

     * @param	

     */

    function stop_previous_item(){

		if($this->debug>0){error_log('New LP - In learnpath::stop_previous_item()',0);}

    	if($this->last != 0 AND $this->last!=$this->current AND is_object($this->items[$this->last]))
    	{
    		if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - '.$this->last.' is object',0);}
    		switch($this->get_type()){
				case '3':
		    		if($this->items[$this->last]->get_type()!='au')
		    		{
			    		if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - '.$this->last.' in lp_type 3 is <> au',0);}
		    			$this->items[$this->last]->close();
			    		//$this->autocomplete_parents($this->last);
		  	    		//$this->update_queue[$this->last] = $this->items[$this->last]->get_status();
		    		}else{
		    			if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - Item is an AU, saving is managed by AICC signals',0);}
		    		}
		    	case '2':
		    		if($this->items[$this->last]->get_type()!='sco')
		    		{
			    		if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - '.$this->last.' in lp_type 2 is <> sco',0);}
		    			$this->items[$this->last]->close();
			    		//$this->autocomplete_parents($this->last);
		  	    		//$this->update_queue[$this->last] = $this->items[$this->last]->get_status();
		    		}else{
		    			if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - Item is a SCO, saving is managed by SCO signals',0);}
		    		}
		    		break;
		    	case '1':
		    	default:
		    		if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - '.$this->last.' in lp_type 1 is asset',0);}
	    			$this->items[$this->last]->close();
		    		break;
    		}
    	}else{
    		if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - No previous element found, ignoring...',0);}
    		return false;
    	}
    	return true;
    }

    /**

     * Updates the default view mode from fullscreen to embedded and inversely

     * @return	string The current default view mode ('fullscreen' or 'embedded')

     */

    function update_default_view_mode()

    {

		if($this->debug>0){error_log('New LP - In learnpath::update_default_view_mode()',0);}

    	$lp_table = Database::get_course_table('lp');

    	$sql = "SELECT * FROM $lp_table WHERE id = ".$this->get_id();

    	$res = api_sql_query($sql);

    	if(Database::num_rows($res)>0){

    		$row = Database::fetch_array($res);

    		$view_mode = $row['default_view_mod'];

    		if($view_mode == 'fullscreen'){

    			$view_mode = 'embedded';

    		}elseif($view_mode == 'embedded'){

    			$view_mode = 'fullscreen';

    		}

    		$sql = "UPDATE $lp_table SET default_view_mod = '$view_mode' WHERE id = ".$this->get_id();

    		$res = api_sql_query($sql);

   			$this->mode = $view_mode;

	    	return $view_mode;

    	}else{

    		if($this->debug>2){error_log('New LP - Problem in update_default_view() - could not find LP '.$this->get_id().' in DB',0);}

    	}

		return -1;

    }

    /**

     * Updates the default behaviour about auto-commiting SCORM updates

     * @return	boolean	True if auto-commit has been set to 'on', false otherwise

     */

    function update_default_scorm_commit(){

		if($this->debug>0){error_log('New LP - In learnpath::update_default_scorm_commit()',0);}

    	$lp_table = Database::get_course_table('lp');

    	$sql = "SELECT * FROM $lp_table WHERE id = ".$this->get_id();

    	$res = api_sql_query($sql);

    	if(Database::num_rows($res)>0){

    		$row = Database::fetch_array($res);

    		$force = $row['force_commit'];

			if($force == 1){

    			$force = 0;

    			$force_return = false;

    		}elseif($force == 0){

    			$force = 1;

    			$force_return = true;

    		}

    		$sql = "UPDATE $lp_table SET force_commit = $force WHERE id = ".$this->get_id();

    		$res = api_sql_query($sql);

			$this->force_commit = $force_return;

	    	return $force_return;

    	}else{

    		if($this->debug>2){error_log('New LP - Problem in update_default_scorm_commit() - could not find LP '.$this->get_id().' in DB',0);}

    	}

    	return -1;

    }

    /**

     * Updates the "prevent_reinit" value that enables control on reinitialising items on second view

     * @return	boolean	True if prevent_reinit has been set to 'on', false otherwise (or 1 or 0 in this case)

     */

    function update_reinit(){

		if($this->debug>0){error_log('New LP - In learnpath::update_reinit()',0);}

    	$lp_table = Database::get_course_table('lp');

    	$sql = "SELECT * FROM $lp_table WHERE id = ".$this->get_id();

    	$res = api_sql_query($sql);

    	if(Database::num_rows($res)>0){

    		$row = Database::fetch_array($res);

    		$force = $row['prevent_reinit'];

			if($force == 1){

    			$force = 0;

    		}elseif($force == 0){

    			$force = 1;

    		}

    		$sql = "UPDATE $lp_table SET prevent_reinit = $force WHERE id = ".$this->get_id();

    		$res = api_sql_query($sql,__FILE__,__LINE__);

			$this->prevent_reinit = $force;

	    	return $force;

    	}else{

    		if($this->debug>2){error_log('New LP - Problem in update_reinit() - could not find LP '.$this->get_id().' in DB',0);}

    	}

    	return -1;

    }

    /**

     * Updates the "scorm_debug" value that shows or hide the debug window

     * @return	boolean	True if scorm_debug has been set to 'on', false otherwise (or 1 or 0 in this case)

     */

    function update_scorm_debug(){

		if($this->debug>0){error_log('New LP - In learnpath::update_scorm_debug()',0);}

    	$lp_table = Database::get_course_table('lp');

    	$sql = "SELECT * FROM $lp_table WHERE id = ".$this->get_id();

    	$res = api_sql_query($sql);

    	if(Database::num_rows($res)>0){

    		$row = Database::fetch_array($res);

    		$force = $row['debug'];

			if($force == 1){

    			$force = 0;

    		}elseif($force == 0){

    			$force = 1;

    		}

    		$sql = "UPDATE $lp_table SET debug = $force WHERE id = ".$this->get_id();

    		$res = api_sql_query($sql,__FILE__,__LINE__);

			$this->scorm_debug = $force;

	    	return $force;

    	}else{

    		if($this->debug>2){error_log('New LP - Problem in update_scorm_debug() - could not find LP '.$this->get_id().' in DB',0);}

    	}

    	return -1;

    }
    
   /**
	* Function that makes a call to the function sort_tree_array and create_tree_array
	*
	* @author Kevin Van Den Haute
	* 
	* @param unknown_type $array
	*/
	function tree_array($array)
	{
		$array = $this->sort_tree_array($array);
		$this->create_tree_array($array);
	}

	/**
	 * Creates an array with the elements of the learning path tree in it
	 *
	 * @author Kevin Van Den Haute
	 * 
	 * @param array $array
	 * @param int $parent
	 * @param int $depth
	 * @param array $tmp
	 */
	function create_tree_array($array, $parent = 0, $depth = -1, $tmp = array())
	{
		if(is_array($array))
		{
			for($i = 0; $i < count($array); $i++)
			{	
				if($array[$i]['parent_item_id'] == $parent)
				{
					if(!in_array($array[$i]['parent_item_id'], $tmp))
					{
						$tmp[] = $array[$i]['parent_item_id'];
						$depth++;
					}
					
					$this->arrMenu[] = array(
						'id' => $array[$i]['id'],
						'item_type' => $array[$i]['item_type'],
						'title' => $array[$i]['title'],
						'path' => $array[$i]['path'],
						'description' => $array[$i]['description'],
						'parent_item_id' => $array[$i]['parent_item_id'],
						'previous_item_id' => $array[$i]['previous_item_id'],
						'next_item_id' => $array[$i]['next_item_id'],
						'min_score' => $array[$i]['min_score'],
						'max_score' => $array[$i]['max_score'],
						'display_order' => $array[$i]['display_order'],
						'prerequisite' => $array[$i]['prerequisite'],
						'depth' => $depth
						);
					
					$this->create_tree_array($array, $array[$i]['id'], $depth, $tmp);
				}
			}
		}
	}
	
	/**
	 * Sorts a multi dimensional array by parent id and display order
	 * @author Kevin Van Den Haute
	 * 
	 * @param array $array (array with al the learning path items in it)
	 * 
	 * @return array
	 */
	function sort_tree_array($array)
	{
		foreach($array as $key => $row)
		{
			$parent[$key]	= $row['parent_item_id'];
			$position[$key]	= $row['display_order'];
		}
		
		if(count($array) > 0)
			array_multisort($parent, SORT_ASC, $position, SORT_ASC, $array);
		
		return $array;
	}
	
	
	
	/**
	 * Function that creates a table structure with a learning path his modules, chapters and documents.
	 * Also the actions for the modules, chapters and documents are in this table.
	 *
	 * @author Kevin Van Den Haute
	 * 
	 * @param int $lp_id
	 * 
	 * @return string
	 */
	function overview()
	{
		$return = '';
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		
		$sql = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE
				lp_id = " . $this->lp_id;
		
		$result = api_sql_query($sql, __FILE__, __LINE__);
		
		$arrLP = array();
		
		while($row = Database::fetch_array($result))
		{
			$arrLP[] = array(
				'id' => $row['id'],
				'item_type' => $row['item_type'],
				'title' => $row['title'],
				'description' => $row['description'],
				'parent_item_id' => $row['parent_item_id'],
				'previous_item_id' => $row['previous_item_id'],
				'next_item_id' => $row['next_item_id'],
				'display_order' => $row['display_order']);
		}
		
		$this->tree_array($arrLP);
		
		$arrLP = $this->arrMenu;
		
		unset($this->arrMenu);
		
		if(api_is_allowed_to_edit())
			$return .= '<p><a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=build&amp;lp_id=' . $this->lp_id . '">'.get_lang("Advanced").'</a>&nbsp;'.get_lang("BasicOverview").'&nbsp;<a href="lp_controller.php?cidReq='.$_GET['cidReq'].'&action=view&lp_id='.$this->lp_id.'">'.get_lang("Display").'</a></p>';
		
		$return .= '<table class="data_table">' . "\n";
		
			$return .= "\t" . '<tr>' . "\n";
			
				$return .= "\t" . '<th width="75%">'.get_lang("Title").'</th>' . "\n";
				//$return .= "\t" . '<th>'.get_lang("Description").'</th>' . "\n";
				$return .= "\t" . '<th>'.get_lang("Move").'</th>' . "\n";
				$return .= "\t" . '<th>'.get_lang("Actions").'</th>' . "\n";
			
			$return .= "\t" . '</tr>' . "\n";
			
			for($i = 0; $i < count($arrLP); $i++)
			{
				if($arrLP[$i]['description'] == '')
					$arrLP[$i]['description'] = '&nbsp;';
				
				if (($i % 2)==0) { $oddclass="row_odd"; } else { $oddclass="row_even"; }
				
				$return .= "\t" . '<tr class="'.$oddclass.'">' . "\n";
					
					$return .= "\t\t" . '<td style="padding-left:' . $arrLP[$i]['depth'] * 10 . 'px;"><img align="left" src="../img/lp_' . $arrLP[$i]['item_type'] . '.png" style="margin-right:3px;" />' . stripslashes($arrLP[$i]['title']) . '</td>' . "\n";
					//$return .= "\t\t" . '<td>' . stripslashes($arrLP[$i]['description']) . '</td>' . "\n";
					
					if(api_is_allowed_to_edit())
					{
						$return .= "\t\t" . '<td>' . "\n";
							
							if($arrLP[$i]['previous_item_id'] != 0)
							{
								$return .= "\t\t\t" . '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=move_item&amp;direction=up&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '">';
									$return .= '<img alt="" src="../img/arrow_up_' . ($arrLP[$i]['depth'] % 3) . '.gif" />';
								$return .= '</a>' . "\n";
							}
							else
								$return .= "\t\t\t" . '<img alt="" src="../img/blanco.png" title="" />' . "\n";
							
							if($arrLP[$i]['next_item_id'] != 0)
							{
								$return .= "\t\t\t" . '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=move_item&amp;direction=down&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '">';
									$return .= '<img src="../img/arrow_down_' . ($arrLP[$i]['depth'] % 3) . '.gif" />';
								$return .= '</a>' . "\n";
							}
							else
								$return .= "\t\t\t" . '<img alt="" src="../img/blanco.png" title="" />' . "\n";
							
						$return .= "\t\t" . '</td>' . "\n";
						
						$return .= "\t\t" . '<td>' . "\n";
						
						if($arrLP[$i]['item_type'] != 'dokeos_chapter' && $arrLP[$i]['item_type'] != 'dokeos_module')
						{
							$return .= "\t\t\t" . '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=edit_item&amp;view=build&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '">';
								$return .= '<img alt="" src="../img/edit.gif" title="' . get_lang('_edit_learnpath_module') . '" />';
							$return .= '</a>' . "\n";
						}
						else
						{
							$return .= "\t\t\t" . '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=edit_item&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '">';
								$return .= '<img alt="" src="../img/edit.gif" title="' . get_lang('_edit_learnpath_module') . '" />';
							$return .= '</a>' . "\n";
						}
						
						$return .= "\t\t\t" . '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=delete_item&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '" onclick="return confirmation(\'' . $arrLP[$i]['title'] . '\');">';
							$return .= '<img alt="" src="../img/delete.gif" title="' . get_lang('_delete_learnpath_module') . '" />';
						$return .= '</a>' . "\n";
						
						$return .= "\t\t" . '</td>' . "\n";
					}
					
				$return .= "\t" . '</tr>' . "\n";
			}
			
			if(count($arrLP) == 0)
			{
				$return .= "\t" . '<tr>' . "\n";
					$return .= "\t\t" . '<td colspan="4">'.get_lang("NoItemsInLp").'</td>' . "\n";
				$return .= "\t" . '</tr>' . "\n";
			}
		
		$return .= '</table>' . "\n";
		
		return $return;
	}
	
	/**
	 * This functions builds the LP tree based on data from the database.
	 *
	 * @return string
	 * @uses dtree.js :: necessary javascript for building this tree
	 */
	function build_tree()
	{
		$return = "<script type=\"text/javascript\">\n";
		
		$return .= "\tm = new dTree('m');\n\n";
		
		$return .= "\tm.config.folderLinks		= true;\n";
		$return .= "\tm.config.useCookies		= true;\n";
		$return .= "\tm.config.useIcons			= true;\n";
		$return .= "\tm.config.useLines			= true;\n";
		$return .= "\tm.config.useSelection		= true;\n";
		$return .= "\tm.config.useStatustext	= false;\n\n";
		
		$menu	= 0;
		$parent	= '';
		
		$return .= "\tm.add(" . $menu . ", -1, '" . addslashes($this->name) . "');\n";
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		
		$sql = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE
				lp_id = " . $this->lp_id;
		
		$result = api_sql_query($sql, __FILE__, __LINE__);
		
		$arrLP = array();
		
		while($row = Database::fetch_array($result))
		{
			$arrLP[] = array(
				'id' => $row['id'],
				'item_type' => $row['item_type'],
				'title' => $row['title'],
				'path' => $row['path'],
				'description' => $row['description'],
				'parent_item_id' => $row['parent_item_id'],
				'previous_item_id' => $row['previous_item_id'],
				'next_item_id' => $row['next_item_id'],
				'display_order' => $row['display_order']);
		}
		
		$this->tree_array($arrLP);
		
		$arrLP = $this->arrMenu;
		
		unset($this->arrMenu);
		
		for($i = 0; $i < count($arrLP); $i++)
		{
			$menu_page = $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=view_item&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $_SESSION['oLP']->lp_id;
			if(file_exists("../img/lp_" . $arrLP[$i]['item_type'] . ".png"))
			{
				$return .= "\tm.add(" . $arrLP[$i]['id'] . ", " . $arrLP[$i]['parent_item_id'] . ", '" . $arrLP[$i]['title'] . "', '" . $menu_page . "', '', '', '../img/lp_" . $arrLP[$i]['item_type'] . ".png', '../img/lp_" . $arrLP[$i]['item_type'] . ".png');\n";
			}
			else if(file_exists("../img/lp_" . $arrLP[$i]['item_type'] . ".gif"))
			{
				$return .= "\tm.add(" . $arrLP[$i]['id'] . ", " . $arrLP[$i]['parent_item_id'] . ", '" . $arrLP[$i]['title'] . "', '" . $menu_page . "', '', '', '../img/lp_" . $arrLP[$i]['item_type'] . ".gif', '../img/lp_" . $arrLP[$i]['item_type'] . ".gif');\n";
			}
			else
			{
				$return .= "\tm.add(" . $arrLP[$i]['id'] . ", " . $arrLP[$i]['parent_item_id'] . ", '" . $arrLP[$i]['title'] . "', '" . $menu_page . "', '', '', '../img/lp_document.png', '../img/lp_document.png');\n";
			}
			if($menu < $arrLP[$i]['id'])
				$menu = $arrLP[$i]['id'];
		}
		
		$return .= "\n\tdocument.write(m);\n";
		$return .= "\t if(!m.selectedNode) m.s(1);";
		$return .= "</script>\n";
		
		return $return;
	}
	
	/**
	 * Create a new document //still needs some finetuning
	 *
	 * @param array $_course
	 * @return string
	 */
	function create_document($_course)
	{
		$dir = isset($_GET['dir']) ? $_GET['dir'] : $_POST['dir']; // please do not modify this dirname formatting
		
		if(strstr($dir, '..'))
			$dir = '/';
		
		if($dir[0] == '.')
			$dir = substr($dir, 1);
		
		if($dir[0] != '/')
			$dir = '/'.$dir;
		
		if($dir[strlen($dir) - 1] != '/')
			$dir .= '/';
		
		$filepath = api_get_path('SYS_COURSE_PATH') . $_course['path'] . '/document' . $dir;
		
		if(!is_dir($filepath))
		{
			$filepath = api_get_path('SYS_COURSE_PATH') . $_course['path'] . '/document/';
			
			$dir = '/';
		}
		
		$title		= replace_dangerous_char($_POST['title']);
		$filename	= $title;
		$content	= $_POST['content_lp'];
		
		$tmp_filename = $filename;
									
		while(file_exists($filepath . $tmp_filename . '.html'))
			$tmp_filename = $filename . '_' . ++$i;
									
		$filename = $tmp_filename . '.html';
		
		$content = stripslashes(text_filter($content));
		
		$path_to_remove = api_get_path('WEB_COURSE_PATH') . $_course['path'] . '/document' . $dir;
		$content = str_replace($path_to_remove, './', $content);
		
		if(!file_exists($filepath . $filename))
		{
			if($fp = @fopen($filepath . $filename, 'w'))
			{	
				fputs($fp, $content);
				fclose($fp);
											
				$file_size = filesize($filepath . $filename);
				$save_file_path = $dir . $filename;
											
				$document_id = add_document($_course, $save_file_path, 'file', $file_size, $filename . '.html');
											
				if($document_id)
				{
					api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $_user['user_id'], $to_group_id);
									
					//update parent folders
					//item_property_update_on_folder($_course, $_GET['dir'], $_user['user_id']);
									
					$new_comment = (isset($_POST['comment'])) ? trim($_POST['comment']) : '';
					$new_title = (isset($_POST['title'])) ? trim($_POST['title']) : '';
												
					if($new_comment || $new_title)
					{
						$tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
						$ct = '';
						
						if($new_comment)
							$ct .= ", comment='" . $new_comment . "'";
						
						if($new_title)
							$ct .= ", title='" . $new_title . ".html	'";
						
						$sql_update = "
							UPDATE " . $tbl_doc . "
							SET " . substr($ct, 1) . "
							WHERE id = " . $document_id;
						api_sql_query($sql_update, __FILE__, __LINE__);
					}
				}
											
				return $document_id;
			}
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $_course
	 */
	function edit_document($_course)
	{
		global $_configuration;
		
		
		$dir = isset($_GET['dir']) ? $_GET['dir'] : $_POST['dir']; // please do not modify this dirname formatting
		
		if(strstr($dir, '..'))
			$dir = '/';
		
		if($dir[0] == '.')
			$dir = substr($dir, 1);
		
		if($dir[0] != '/')
			$dir = '/'.$dir;
		
		if($dir[strlen($dir) - 1] != '/')
			$dir .= '/';
		
		$filepath = api_get_path('SYS_COURSE_PATH') . $_course['path'] . '/document'.$dir;
		
		if(!is_dir($filepath))
		{
			$filepath = api_get_path('SYS_COURSE_PATH') . $_course['path'] . '/document/';
			
			$dir = '/';
		}
		
		$table_doc = Database::get_course_table(TABLE_DOCUMENT);
		
		$sql = "
			SELECT path
			FROM " . $table_doc . "
			WHERE id = " . $_POST['path'];
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$row = Database::fetch_array($res);
		$content	= stripslashes($_POST['content_lp']);
		$file		= $filepath . $row['path'];
		
		
		if($fp = @fopen($file, 'w'))
		{
			$content = text_filter($content);
			$content = str_replace(api_get_path('WEB_COURSE_PATH'), $_configuration['url_append'].'/courses/', $content);
			
			fputs($fp, $content);
			fclose($fp);
		}
	}
	
	/**
	 * Displays the selected item, with a panel for manipulating the item
	 *
	 * @param int $item_id
	 * @param string $msg
	 * @return string
	 */
	function display_item($item_id, $iframe = true, $msg = '')
	{
		global $_course; //will disappear
		
		$return = '';
		
		if(is_numeric($item_id))
		{
			$tbl_lp_item	= Database::get_course_table('lp_item');
			$tbl_doc		= Database::get_course_table(TABLE_DOCUMENT);
			
			$sql = "
				SELECT
					lp.*
				FROM " . $tbl_lp_item . " as lp
				WHERE
					lp.id = " . $item_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			while($row = Database::fetch_array($result))
			{
				$return .= $this->display_manipulate($item_id, $row['item_type']);
				
				$return .= '<div style="padding:10px;">';
				
				if($msg != '')
					$return .= $msg;
				
				$return .= '<p class="lp_title">' . stripslashes($row['title']) . '</p>';
				//$return .= '<p class="lp_text">' . ((trim($row['description']) == '') ? 'no description' : stripslashes($row['description'])) . '</p>';
				
				//$return .= '<hr />';
				
				if($row['item_type'] == TOOL_DOCUMENT)
				{
					$tbl_doc = Database :: get_course_table(TABLE_DOCUMENT);
					$sql_doc = "SELECT path FROM " . $tbl_doc . " WHERE id = " . $row['path'];
					$result=api_sql_query($sql_doc);
					$path_file=mysql_result($result,0,0);					
					$path_parts = pathinfo($path_file);
					
					if(in_array($path_parts['extension'],array('html','txt','png', 'jpg', 'JPG', 'jpeg', 'JPEG', 'gif', 'swf')))
					{
						$return .= $this->display_document($row['path'], true, true);
					}
				}
					
				$return .= '</div>';
			}
		}
		
		return $return;
	}
	
	/**
	 * Shows the needed forms for editing a specific item
	 *
	 * @param int $item_id
	 * @return string
	 */
	function display_edit_item($item_id)
	{
		global $_course; //will disappear
		
		$return = '';
		
		if(is_numeric($item_id))
		{
			$tbl_lp_item = Database::get_course_table('lp_item');
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE id = " . $item_id;
			
			$res = api_sql_query($sql, __FILE__, __LINE__);
			$row = Database::fetch_array($res);
			
			switch($row['item_type'])
			{
				case 'dokeos_chapter':
					
					if(isset($_GET['view']) && $_GET['view'] == 'build')
					{
						$return .= $this->display_manipulate($item_id, $row['item_type']);
						$return .= $this->display_item_form($row['item_type'], get_lang("EditCurrentChapter").' :', 'edit', $item_id, $row);
					}
					else
					{
						$return .= $this->display_item_small_form($row['item_type'], get_lang("EditCurrentChapter").' :', $row);
					}
					
					break;
					
				case TOOL_DOCUMENT:
				
					$tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
			
					$sql_step = "
						SELECT
							lp.*,
							doc.path as dir
						FROM " . $tbl_lp_item . " as lp
						LEFT JOIN " . $tbl_doc . " as doc ON doc.id = lp.path
						WHERE
							lp.id = " . $item_id;
					$res_step = api_sql_query($sql_step, __FILE__, __LINE__);
					$row_step = Database::fetch_array($res_step);
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_document_form('edit', $item_id, $row_step);
					
					break;
				
				case TOOL_LINK:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_link_form('edit', $item_id, $row);
					
					break;
				
				case 'dokeos_module':
				
					if(isset($_GET['view']) && $_GET['view'] == 'build')
					{
						$return .= $this->display_manipulate($item_id, $row['item_type']);
						$return .= $this->display_item_form($row['item_type'], get_lang("EditCurrentModule").' :', 'edit', $item_id, $row);
					}
					else
					{
						$return .= $this->display_item_small_form($row['item_type'], get_lang("EditCurrentModule").' :', $row);
					}
		
					break;
				
				case TOOL_QUIZ:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_quiz_form('edit', $item_id, $row);
					
					break;
				
				case TOOL_STUDENTPUBLICATION:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_student_publication_form('edit', $item_id, $row);
					
					break;
					
				case TOOL_FORUM:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_forum_form('edit', $item_id, $row);
					
					break;
					
				case TOOL_THREAD:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_thread_form('edit', $item_id, $row);
					
					break;
			}
		}
		
		return $return;
	}
	
	/**
	 * Function that displays a list with al the resources that could be added to the learning path
	 *
	 * @return string
	 */
	function display_resources()
	{
		global $_course; //TODO: don't use globals
		
		$return = '<div style="margin:3px 10px;">' . "\n";
		
			$return .= '<p class="lp_title" style="margin-top:0;">'.get_lang("CreateNewStep").'</p>';
		
			$return .= '<div><a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_DOCUMENT . '&amp;lp_id=' . $_SESSION['oLP']->lp_id . '">'.get_lang("NewDocument").'</a></div>';
			
			$return .= '<p class="lp_title" style="margin-top:10px;">'.get_lang("UseAnExistingResource").'</p>';
			
			/* get all the docs */
			$return .= $this->get_documents(); 
			
			/* get all the exercises */
			$return .= $this->get_exercises();
			
			/* get all the links */
			$return .= $this->get_links();
			
			/* get al the student publications */
			$return .= $this->get_student_publications();
			
			/* get al the forums */
			$return .= $this->get_forums();
		
		$return .= '</div>' . "\n";
		
		return $return;
	}
	
	/**
	 * Returns the extension of a document
	 *
	 * @param unknown_type $filename
	 * @return unknown
	 */
	function get_extension($filename)
	{
		$explode = explode('.', $filename);
		
		return $explode[count($explode) - 1];
	}
	
	/**
	 * Displays a document by id
	 *
	 * @param unknown_type $id
	 * @return unknown
	 */
	function display_document($id, $show_title = false, $iframe = true, $edit_link = false)
	{
		global $_course; //temporary
			
		$return = '';
		
		$tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
		
		$sql_doc = "
			SELECT *
			FROM " . $tbl_doc . "
			WHERE id = " . $id;
		$res_doc = api_sql_query($sql_doc, __FILE__, __LINE__);	
		$row_doc = Database::fetch_array($res_doc);
		
		//if($show_title)
			//$return .= '<p class="lp_title">' . $row_doc['title'] . ($edit_link ? ' [ <a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_DOCUMENT . '&amp;file=' . $_GET['file'] . '&amp;edit=true&amp;lp_id=' . $_GET['lp_id'] . '">Edit this document</a> ]' : '') . '</p>';
		
		//TODO: add a path filter
		if($iframe){
			$return .= '<iframe frameborder="0" src="' . api_get_path(WEB_COURSE_PATH) . $_course['path'] . '/document' . $row_doc['path'] . '" style="background:#FFFFFF; border:1px solid #CCCCCC; height:490px; width:100%; margin-top: 20px;"></iframe>';
		}
		else{
			$return .= file_get_contents(api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/document' . $row_doc['path']);
		}
		
		return $return;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $action
	 * @param unknown_type $id
	 * @param unknown_type $extra_info
	 * @return unknown
	 */
	function display_quiz_form($action = 'add', $id = 0, $extra_info = '')
	{
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		$tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
		
		if($id != 0 && is_array($extra_info))
		{
			$item_title			= stripslashes($extra_info['title']);
			$item_description	= stripslashes($extra_info['description']);
		}
		elseif(is_numeric($extra_info))
		{
			$sql_quiz = "
				SELECT
					title,
					description
				FROM " . $tbl_quiz . "
				WHERE id = " . $extra_info;
			
			$result = api_sql_query($sql_quiz, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			
			$item_title = $row['title'];
			$item_description = $row['description'];
		}
		else
		{
			$item_title			= '';
			$item_description	= '';
		}
				
		$return = '<div style="margin:3px 10px;">';
			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			$arrLP = array();
			
			while($row = Database::fetch_array($result))
			{
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
					'prerequisite' => $row['prerequisite']);
			}
			
			$this->tree_array($arrLP);
			
			$arrLP = $this->arrMenu;
			
			unset($this->arrMenu);
			
			if($action == 'add')
				$return .= '<p class="lp_title">'.get_lang("CreateTheExercise").' :</p>' . "\n";
			elseif($action == 'move')
				$return .= '<p class="lp_title">'.get_lang("MoveTheCurrentExercise").' :</p>' . "\n";
			else
				$return .= '<p class="lp_title">'.get_lang("EditCurrentExecice").' :</p>' . "\n";
			
			if(isset($_GET['edit']) && $_GET['edit'] == 'true')
			{
				$return .= '<div class="lp_message" style="margin-bottom:15px;">';
				
					$return .= '<p class="lp_title">'.get_lang("Warning").' !</p>';
					$return .= get_lang("WarningEditingDocument");
				
				$return .= '</div>';
			}
			
			$return .= '<form method="POST">' . "\n";
			
				$return .= "\t" . '<table cellpadding="0" cellspacing="0" class="lp_form">' . "\n";
				
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td class="label"><label for="idParent">'.get_lang("Parent").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idParent" name="parent" onchange="load_cbo(this.value);" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">' . $this->name . '</option>';
								
								$arrHide = array($id);
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($action != 'add')
									{
										if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide))
										{
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '</option>';
										}
										else
										{
											$arrHide[] = $arrLP[$i]['id'];
										}
									}
									else
									{
										if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '</option>';
									}
								}
								
								reset($arrLP);
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
									
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idPosition">'.get_lang("Position").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idPosition" name="previous" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">First position</option>';
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
									{
										if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
											$selected = 'selected="selected" ';
										elseif($action == 'add')
											$selected = 'selected="selected" ';
										else
											$selected = '';
										
										$return .= "\t\t\t\t\t" . '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">'.get_lang("After").' "' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '"</option>';
									}
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					if($action != 'move')
					{
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idTitle">'.get_lang("Title").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input id="idTitle" name="title" type="text" value="' . $item_title . '" /></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						
						$id_prerequisite=0;
						foreach($arrLP as $key=>$value){
							if($value['id']==$id){
								$id_prerequisite=$value['prerequisite'];
								break;
							}
						}
						
						$arrHide=array();
						for($i = 0; $i < count($arrLP); $i++)
						{
							if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter')
							{
								if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
									$s_selected_position=$arrLP[$i]['id'];
								elseif($action == 'add')
									$s_selected_position=0;
								$arrHide[$arrLP[$i]['id']]['value']=html_entity_decode(stripslashes($arrLP[$i]['title']));
								
							}
						}
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idPrerequisites">'.get_lang("Prerequisites").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><select name="prerequisites" id="prerequisites" style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; width:300px;"><option value="0">'.get_lang("NoPrerequisites").'</option>';
							
							foreach($arrHide as $key => $value){
								if($key==$s_selected_position && $action == 'add'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								elseif($key==$id_prerequisite && $action == 'edit'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								else{
									$return .= '<option value="'.$key.'">'.$value['value'].'</option>';
								}
							}
							
							$return .= "</select></td>";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							//Remove temporaly the test description
							//$return .= "\t\t\t" . '<td class="label"><label for="idDescription">'.get_lang("Description").' :</label></td>' . "\n";
							//$return .= "\t\t\t" . '<td class="input"><textarea id="idDescription" name="description" rows="4">' . $item_description . '</textarea></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
					}
					
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td colspan="2"><input class="button" name="submit_button" type="submit" value="OK" /></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
				
				$return .= "\t" . '</table>' . "\n";	
				
				if($action == 'move')
				{
					$return .= "\t" . '<input name="title" type="hidden" value="' . $item_title . '" />' . "\n";
					$return .= "\t" . '<input name="description" type="hidden" value="' . $item_description . '" />' . "\n";
				}
				
				if(is_numeric($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info . '" />' . "\n";
				}
				elseif(is_array($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />' . "\n";
				}
				
				$return .= "\t" . '<input name="type" type="hidden" value="'.TOOL_QUIZ.'" />' . "\n";
				$return .= "\t" . '<input name="post_time" type="hidden" value="' . time() . '" />' . "\n";
				
			$return .= '</form>' . "\n";
		
		$return .= '</div>' . "\n";
		return $return;
	}
	
/**
	 * Enter description here...
	 *
	 * @param unknown_type $action
	 * @param unknown_type $id
	 * @param unknown_type $extra_info
	 * @return unknown
	 */
	function display_forum_form($action = 'add', $id = 0, $extra_info = '')
	{
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		$tbl_forum = Database::get_course_table(TABLE_FORUM);
		
		if($id != 0 && is_array($extra_info))
		{
			$item_title			= stripslashes($extra_info['title']);
		}
		elseif(is_numeric($extra_info))
		{
			$sql_forum = "
				SELECT
					forum_title as title
				FROM " . $tbl_forum . "
				WHERE forum_id = " . $extra_info;
			
			$result = api_sql_query($sql_forum, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			
			$item_title = $row['title'];
		}
		else
		{
			$item_title			= '';
		}
				
		$return = '<div style="margin:3px 10px;">';
			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			$arrLP = array();
			
			while($row = Database::fetch_array($result))
			{
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
					'prerequisite' => $row['prerequisite']);
			}
			
			$this->tree_array($arrLP);
			
			$arrLP = $this->arrMenu;
			
			unset($this->arrMenu);
			
			if($action == 'add')
				$return .= '<p class="lp_title">'.get_lang("CreateTheForum").' :</p>' . "\n";
			elseif($action == 'move')
				$return .= '<p class="lp_title">'.get_lang("MoveTheCurrentForum").' :</p>' . "\n";
			else
				$return .= '<p class="lp_title">'.get_lang("EditCurrentForum").' :</p>' . "\n";
			
			$return .= '<form method="POST">' . "\n";
			
				$return .= "\t" . '<table cellpadding="0" cellspacing="0" class="lp_form">' . "\n";
				
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td class="label"><label for="idParent">'.get_lang("Parent").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idParent" name="parent" onchange="load_cbo(this.value);" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">' . $this->name . '</option>';
								
								$arrHide = array($id);
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($action != 'add')
									{
										if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide))
										{
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '</option>';
										}
										else
										{
											$arrHide[] = $arrLP[$i]['id'];
										}
									}
									else
									{
										if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '</option>';
									}
								}
								
								reset($arrLP);
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
									
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idPosition">'.get_lang("Position").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idPosition" name="previous" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">First position</option>';
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
									{
										if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
											$selected = 'selected="selected" ';
										elseif($action == 'add')
											$selected = 'selected="selected" ';
										else
											$selected = '';
										
										$return .= "\t\t\t\t\t" . '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">'.get_lang("After").' "' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '"</option>';
									}
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					if($action != 'move')
					{
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idTitle">'.get_lang("Title").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input id="idTitle" name="title" type="text" value="' . $item_title . '" /></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							//Remove temporaly the test description
							//$return .= "\t\t\t" . '<td class="label"><label for="idDescription">'.get_lang("Description").' :</label></td>' . "\n";
							//$return .= "\t\t\t" . '<td class="input"><textarea id="idDescription" name="description" rows="4">' . $item_description . '</textarea></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$id_prerequisite=0;
						foreach($arrLP as $key=>$value){
							if($value['id']==$id){
								$id_prerequisite=$value['prerequisite'];
								break;
							}
						}
						
						$arrHide=array();
						for($i = 0; $i < count($arrLP); $i++)
						{
							if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter')
							{
								if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
									$s_selected_position=$arrLP[$i]['id'];
								elseif($action == 'add')
									$s_selected_position=0;
								$arrHide[$arrLP[$i]['id']]['value']=html_entity_decode(stripslashes($arrLP[$i]['title']));
								
							}
						}
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idPrerequisites">'.get_lang("Prerequisites").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><select name="prerequisites" id="prerequisites" style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; width:300px;"><option value="0">'.get_lang("NoPrerequisites").'</option>';
							
							foreach($arrHide as $key => $value){
								if($key==$s_selected_position && $action == 'add'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								elseif($key==$id_prerequisite && $action == 'edit'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								else{
									$return .= '<option value="'.$key.'">'.$value['value'].'</option>';
								}
							}
							
							$return .= "</select></td>";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
					}
					
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td colspan="2"><input class="button" name="submit_button" type="submit" value="OK" /></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
				
				$return .= "\t" . '</table>' . "\n";	
				
				if($action == 'move')
				{
					$return .= "\t" . '<input name="title" type="hidden" value="' . $item_title . '" />' . "\n";
					$return .= "\t" . '<input name="description" type="hidden" value="' . $item_description . '" />' . "\n";
				}
				
				if(is_numeric($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info . '" />' . "\n";
				}
				elseif(is_array($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />' . "\n";
				}
				
				$return .= "\t" . '<input name="type" type="hidden" value="'.TOOL_FORUM.'" />' . "\n";
				$return .= "\t" . '<input name="post_time" type="hidden" value="' . time() . '" />' . "\n";
				
			$return .= '</form>' . "\n";
		
		$return .= '</div>' . "\n";
		return $return;
	}
	
function display_thread_form($action = 'add', $id = 0, $extra_info = '')
	{
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		$tbl_forum = Database::get_course_table(TABLE_FORUM_THREAD);
		
		if($id != 0 && is_array($extra_info))
		{
			$item_title			= stripslashes($extra_info['title']);
		}
		elseif(is_numeric($extra_info))
		{
			$sql_forum = "
				SELECT
					thread_title as title
				FROM " . $tbl_forum . "
				WHERE thread_id = " . $extra_info;
			
			$result = api_sql_query($sql_forum, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			
			$item_title = $row['title'];
		}
		else
		{
			$item_title			= '';
		}
				
		$return = '<div style="margin:3px 10px;">';
			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			$arrLP = array();
			
			while($row = Database::fetch_array($result))
			{
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
					'prerequisite' => $row['prerequisite']);
			}
			
			$this->tree_array($arrLP);
			
			$arrLP = $this->arrMenu;
			
			unset($this->arrMenu);
			
			if($action == 'add')
				$return .= '<p class="lp_title">'.get_lang("CreateTheForum").' :</p>' . "\n";
			elseif($action == 'move')
				$return .= '<p class="lp_title">'.get_lang("MoveTheCurrentForum").' :</p>' . "\n";
			else
				$return .= '<p class="lp_title">'.get_lang("EditCurrentForum").' :</p>' . "\n";
			
			$return .= '<form method="POST">' . "\n";
			
				$return .= "\t" . '<table cellpadding="0" cellspacing="0" class="lp_form">' . "\n";
				
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td class="label"><label for="idParent">'.get_lang("Parent").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idParent" name="parent" onchange="load_cbo(this.value);" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">' . $this->name . '</option>';
								
								$arrHide = array($id);
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($action != 'add')
									{
										if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide))
										{
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '</option>';
										}
										else
										{
											$arrHide[] = $arrLP[$i]['id'];
										}
									}
									else
									{
										if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '</option>';
									}
								}
								
								reset($arrLP);
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
									
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idPosition">'.get_lang("Position").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idPosition" name="previous" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">First position</option>';
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
									{
										if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
											$selected = 'selected="selected" ';
										elseif($action == 'add')
											$selected = 'selected="selected" ';
										else
											$selected = '';
										
										$return .= "\t\t\t\t\t" . '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">'.get_lang("After").' "' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '"</option>';
									}
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					if($action != 'move')
					{
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idTitle">'.get_lang("Title").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input id="idTitle" name="title" type="text" value="' . $item_title . '" /></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							//Remove temporaly the test description
							//$return .= "\t\t\t" . '<td class="label"><label for="idDescription">'.get_lang("Description").' :</label></td>' . "\n";
							//$return .= "\t\t\t" . '<td class="input"><textarea id="idDescription" name="description" rows="4">' . $item_description . '</textarea></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$id_prerequisite=0;
						foreach($arrLP as $key=>$value){
							if($value['id']==$id){
								$id_prerequisite=$value['prerequisite'];
								break;
							}
						}
						
						$arrHide=array();
						for($i = 0; $i < count($arrLP); $i++)
						{
							if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter')
							{
								if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
									$s_selected_position=$arrLP[$i]['id'];
								elseif($action == 'add')
									$s_selected_position=0;
								$arrHide[$arrLP[$i]['id']]['value']=html_entity_decode(stripslashes($arrLP[$i]['title']));
								
							}
						}
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idPrerequisites">'.get_lang("Prerequisites").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><select name="prerequisites" id="prerequisites" style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; width:300px;"><option value="0">'.get_lang("NoPrerequisites").'</option>';
							
							foreach($arrHide as $key => $value){
								if($key==$s_selected_position && $action == 'add'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								elseif($key==$id_prerequisite && $action == 'edit'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								else{
									$return .= '<option value="'.$key.'">'.$value['value'].'</option>';
								}
							}
							
							$return .= "</select></td>";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
					}
					
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td colspan="2"><input class="button" name="submit_button" type="submit" value="OK" /></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
				
				$return .= "\t" . '</table>' . "\n";	
				
				if($action == 'move')
				{
					$return .= "\t" . '<input name="title" type="hidden" value="' . $item_title . '" />' . "\n";
					$return .= "\t" . '<input name="description" type="hidden" value="' . $item_description . '" />' . "\n";
				}
				
				if(is_numeric($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info . '" />' . "\n";
				}
				elseif(is_array($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />' . "\n";
				}
				
				$return .= "\t" . '<input name="type" type="hidden" value="'.TOOL_THREAD.'" />' . "\n";
				$return .= "\t" . '<input name="post_time" type="hidden" value="' . time() . '" />' . "\n";
				
			$return .= '</form>' . "\n";
		
		$return .= '</div>' . "\n";
		return $return;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $item_type
	 * @param unknown_type $title
	 * @param unknown_type $action
	 * @param unknown_type $id
	 * @param unknown_type $extra_info
	 * @return unknown
	 */
	function display_item_form($item_type, $title = '', $action = 'add', $id = 0, $extra_info = 'new')
	{
		$tbl_lp_item = Database::get_course_table('lp_item');
		
		if($id != 0 && is_array($extra_info))
		{
			$item_title			= stripslashes($extra_info['title']);
			$item_description	= stripslashes($extra_info['description']);
		}
		else
		{
			$item_title			= '';
			$item_description	= '';
		}
	
		$return = '<div style="margin:3px 10px;">';
			
		if($id != 0 && is_array($extra_info))
			$parent = $extra_info['parent_item_id'];
		else
			$parent = 0;
		
		$sql = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE
				lp_id = " . $this->lp_id;
		
		if($item_type == 'module')
			$sql .= " AND parent_item_id = 0";
		
		$result = api_sql_query($sql, __FILE__, __LINE__);
		
		$arrLP = array();
		
		while($row = Database::fetch_array($result))
		{
			$arrLP[] = array(
				'id' => $row['id'],
				'item_type' => $row['item_type'],
				'title' => $row['title'],
				'path' => $row['path'],
				'description' => $row['description'],
				'parent_item_id' => $row['parent_item_id'],
				'previous_item_id' => $row['previous_item_id'],
				'next_item_id' => $row['next_item_id'],
				'display_order' => $row['display_order']);
		}
		
		$this->tree_array($arrLP);
		
		$arrLP = $this->arrMenu;
		
		unset($this->arrMenu);
		
		$return .= '<p class="lp_title">' . $title . '</p>' . "\n";

		require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
		
		$form = new FormValidator('form','POST',$_SERVER["PHP_SELF"]."?".$_SERVER["QUERY_STRING"]);
		
		$defaults["title"]=html_entity_decode($item_title);
		$defaults["description"]=html_entity_decode($item_description);
		
		$form->addElement('html',$return);
					
		//$arrHide = array($id);
		
		$arrHide[0]['value']=$this->name;
		$arrHide[0]['padding']=3;
		
		if($item_type != 'module' && $item_type != 'dokeos_module')
		{
			for($i = 0; $i < count($arrLP); $i++)
			{
				if($action != 'add'){
					if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)){
						$arrHide[$arrLP[$i]['id']]['value']=html_entity_decode(stripslashes($arrLP[$i]['title']));
						$arrHide[$arrLP[$i]['id']]['padding']=3+ $arrLP[$i]['depth'] * 10;
						if($parent == $arrLP[$i]['id']){
							$s_selected_parent=$arrHide[$arrLP[$i]['id']];
						}
					}
				}
				else{
					if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter'){
						$arrHide[$arrLP[$i]['id']]['value']=html_entity_decode(stripslashes($arrLP[$i]['title']));
						$arrHide[$arrLP[$i]['id']]['padding']=3+ $arrLP[$i]['depth'] * 10;
						if($parent == $arrLP[$i]['id']){
							$s_selected_parent=$arrHide[$arrLP[$i]['id']];
						}
					}
				}
			}
			
			$parent_select = &$form->addElement('select', 'parent', get_lang("Parent")." :", '', 'style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; width:300px;" onchange="load_cbo(this.value);"');

			foreach($arrHide as $key => $value){
				$parent_select->addOption($value['value'],$key,'style="padding-left:'.$value['padding'].'px;"');
			}
			$parent_select -> setSelected($s_selected_parent);
			
		}
		
		reset($arrLP);
		
		$arrHide=array();

		//POSITION
		for($i = 0; $i < count($arrLP); $i++)
		{
			if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
			{
				if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
					$s_selected_position=$arrLP[$i]['id'];
				elseif($action == 'add')
					$s_selected_position=$arrLP[$i]['id'];
				
				$arrHide[$arrLP[$i]['id']]['value']=get_lang("After").' "' . html_entity_decode(stripslashes($arrLP[$i]['title']));
				
			}
		}
		
		$position = &$form->addElement('select', 'previous', get_lang("Position")." :", '', 'id="idPosition" style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; width:300px;"');
		
		$position->addOption(get_lang('FirstPosition'),$key,'style="padding-left:'.$value['padding'].'px;"');
		foreach($arrHide as $key => $value){
			$position->addOption($value['value'],$key,'style="padding-left:'.$value['padding'].'px;"');
		}
		if(empty($s_selected_position))
		$position -> setSelected($s_selected_position);
		reset($arrLP);
		
		if($action != 'move'){
			$form->addElement('text','title', get_lang('Title').' :','id="idTitle" style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; padding:1px 2px; width:300px;"');
			//$form->addElement('textarea','description',get_lang("Description").' :', 'id="idDescription"  style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; padding:1px 2px; width:300px;"');
			
		}
		
		$form->addElement('submit', 'submit_button', get_lang('Ok'), 'style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; padding:1px 2px; width:75px;"');
		
		if($item_type == 'module' || $item_type == 'dokeos_module')
		{
			$form->addElement('hidden', 'parent', '0');
		}
		
		
		$form->addElement('hidden', 'type', 'dokeos_'.$item_type);
		$form->addElement('hidden', 'post_time', time());
			
		$form->addElement('html','</div>');
		
		$form->setDefaults($defaults);
		
		return $form->return_form();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $action
	 * @param unknown_type $id
	 * @param unknown_type $extra_info
	 * @return unknown
	 */
	function display_document_form($action = 'add', $id = 0, $extra_info = 'new')
	{
		
		echo '
		<style>
		.row{
			width:100%;
		}
		div.row div.label {
			width: 75px;
		}
		
		div.row div.formw {
			width: 85%;
		}
		</style>';
			
		$tbl_lp_item = Database::get_course_table('lp_item');
		$tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
		
		$path_parts = pathinfo($extra_info['dir']);		
		$no_display_edit_textarea=false;
		
		//If action==edit document
		//We don't display the document form if it's not an editable document (html or txt file)
		if($action=="edit"){
			if(is_array($extra_info)){
				if($path_parts['extension']!="txt" && $path_parts['extension']!="html"){
					$no_display_edit_textarea=true;
				}
			}
		}
		
		$no_display_add=false;
		
		//If action==add an existing document
		//We don't display the document form if it's not an editable document (html or txt file)
		if($action=="add"){
			if(is_numeric($extra_info)){
				
				$sql_doc = "SELECT path FROM " . $tbl_doc . "WHERE id = " . $extra_info;
				$result=api_sql_query($sql_doc);
				$path_file=mysql_result($result,0,0);				
				
				$path_parts = pathinfo($path_file);
				
				if($path_parts['extension']!="txt" && $path_parts['extension']!="html"){
					$no_display_add=true;
				}
			}
		}
		
		if($id != 0 && is_array($extra_info))
		{
			$item_title			= stripslashes($extra_info['title']);
			$item_description	= stripslashes($extra_info['description']);	
			if(empty($item_title))
			{				
				$path_parts = pathinfo($extra_info['path']);
				$item_title = stripslashes($path_parts['filename']);
			}
		}
		elseif(is_numeric($extra_info))
		{
			$sql_doc = "
				SELECT path, title
				FROM " . $tbl_doc . "
				WHERE id = " . $extra_info;
			
			$result = api_sql_query($sql_doc, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			
			$explode = explode('.', $row['title']);
			
			if(count($explode)>1){
				for($i = 0; $i < count($explode) - 1; $i++)
					$item_title .= $explode[$i];
			}
			else{
				$item_title=$row['title'];
			}
			
			$item_title = str_replace('_', ' ', $item_title);
			
			if(empty($item_title))
			{
				$path_parts = pathinfo($row['path']);
				$item_title = stripslashes($path_parts['filename']);
			}
			
		}
		else
		{
			$item_title			= '';
			$item_description	= '';
		}
				
		$return = '<div style="margin:3px 10px;">';
			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			$arrLP = array();
			
			while($row = Database::fetch_array($result))
			{
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
					'prerequisite' => $row['prerequisite']);
			}
			
			$this->tree_array($arrLP);
			
			$arrLP = $this->arrMenu;
			
			unset($this->arrMenu);
			
			if($action == 'add')
				$return .= '<p class="lp_title">'.get_lang("CreateTheDocument").' :</p>' . "\n";
			elseif($action == 'move')
				$return .= '<p class="lp_title">'.get_lang("MoveTheCurrentDocument").' :</p>' . "\n";
			else
				$return .= '<p class="lp_title">'.get_lang("EditTheCurrentDocument").' :</p>' . "\n";
			
			if(isset($_GET['edit']) && $_GET['edit'] == 'true')
			{
				$return .= '<div class="lp_message" style="margin-bottom:15px;">';
				
					$return .= '<p class="lp_title">'.get_lang("Warning").' !</p>';
					$return .= get_lang("WarningEditingDocument");
				
				$return .= '</div>';
			}
			/*
			if($no_display_add==true){
				$return .= '<div class="lp_message" style="margin-bottom:15px;">';
				$return .= get_lang("CantEditDocument");
				$return .= '</div>';
				return $return;
			}
			*/
			require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
			
			$form = new FormValidator('form','POST',$_SERVER["PHP_SELF"]."?".$_SERVER["QUERY_STRING"]);
			
			$defaults["title"]=html_entity_decode($item_title);
			$defaults["description"]=html_entity_decode($item_description);			
		
			$form->addElement('html',$return);
						
			//$arrHide = array($id);
			
			$arrHide[0]['value']=$this->name;
			$arrHide[0]['padding']=3;
			
			for($i = 0; $i < count($arrLP); $i++)
			{
				if($action != 'add'){
					if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)){
						$arrHide[$arrLP[$i]['id']]['value']=html_entity_decode(stripslashes($arrLP[$i]['title']));
						$arrHide[$arrLP[$i]['id']]['padding']=3+ $arrLP[$i]['depth'] * 10;
						if($parent == $arrLP[$i]['id']){
							$s_selected_parent=$arrHide[$arrLP[$i]['id']];
						}
					}
				}
				else{
					if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir'){
						$arrHide[$arrLP[$i]['id']]['value']=html_entity_decode(stripslashes($arrLP[$i]['title']));
						$arrHide[$arrLP[$i]['id']]['padding']=3+ $arrLP[$i]['depth'] * 10;
						if($parent == $arrLP[$i]['id']){
							$s_selected_parent=$arrHide[$arrLP[$i]['id']];
						}
					}
				}
			}
			$parent_select = &$form->addElement('select', 'parent', get_lang("Parent")." :", '', 'style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; width:300px;" onchange="load_cbo(this.value);"');

			foreach($arrHide as $key => $value){
				$parent_select->addOption($value['value'],$key,'style="padding-left:'.$value['padding'].'px;"');
			}
			$parent_select -> setSelected($parent);
			reset($arrLP);
			
			$arrHide=array();
			
			//POSITION
			for($i = 0; $i < count($arrLP); $i++)
			{
				if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
				{
					if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
						$s_selected_position=$arrLP[$i]['id'];
					elseif($action == 'add')
						$s_selected_position=$arrLP[$i]['id'];
					
					$arrHide[$arrLP[$i]['id']]['value']=get_lang("After").' "' . html_entity_decode(stripslashes($arrLP[$i]['title'])).'"';
					
				}
			}
			
			$position = &$form->addElement('select', 'previous', get_lang("Position")." :", '', 'id="idPosition" style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; width:300px;"');
			$position->addOption(get_lang("FirstPosition"),0,'style="padding-left:3px;"');
			
			foreach($arrHide as $key => $value){
				$position->addOption($value['value'],$key,'style="padding-left:'.$value['padding'].'px;"');
			}
			$position -> setSelected($s_selected_position);
			reset($arrLP);
			
			if($action != 'move'){
				$form->addElement('text','title', get_lang('Title').' :','id="idTitle" style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; padding:1px 2px; width:300px;"');
				//$form->addElement('textarea','description',get_lang("Description").' :', 'id="idDescription"  style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; padding:1px 2px; width:300px;"');
				
				$id_prerequisite=0;
				foreach($arrLP as $key=>$value){
					if($value['id']==$id){
						$id_prerequisite=$value['prerequisite'];
						break;
					}
				}

				$select_prerequisites=$form->addElement('select', 'prerequisites', get_lang('Prerequisites'), '', 'id="prerequisites" style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; width:300px;"');
				$select_prerequisites->addOption(get_lang("NoPrerequisites"),0,'style="padding-left:3px;"');
				
				$arrHide=array();

				for($i = 0; $i < count($arrLP); $i++)
				{
					if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter')
					{
						if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
							$s_selected_position=$arrLP[$i]['id'];
						elseif($action == 'add')
							$s_selected_position=$arrLP[$i]['id'];
						
						$arrHide[$arrLP[$i]['id']]['value']=html_entity_decode(stripslashes($arrLP[$i]['title']));
						
					}
				}
				
				foreach($arrHide as $key => $value){
					$select_prerequisites->addOption($value['value'],$key,'style="padding-left:'.$value['padding'].'px;"');
					if($key==$s_selected_position && $action == 'add'){
						$select_prerequisites -> setSelected(0);
					}
					elseif($key==$id_prerequisite && $action == 'edit'){
						$select_prerequisites -> setSelected($id_prerequisite);
					}
				}
				
				if(!$no_display_add)
				{
					if(($extra_info == 'new' || $extra_info['item_type'] == TOOL_DOCUMENT || $_GET['edit'] == 'true'))
					{
						
						if(isset($_POST['content']))
							$content = stripslashes($_POST['content']);
						elseif(is_array($extra_info)){
							//If it's an html document or a text file
							if(!$no_display_edit_textarea){
								$content = $this->display_document($extra_info['path'], false, false);
							}
						}
						elseif(is_numeric($extra_info))
							$content = $this->display_document($extra_info, false, false);
						else
							$content = '';
						
						
						if(!$no_display_edit_textarea){
							$form->addElement('html_editor','content_lp',get_lang("Content")." :");
							$defaults["content_lp"]=$content;
						}
						
					}
					
					elseif(is_numeric($extra_info))
					{
						$return = $this->display_document($extra_info, true, true, true);
						$form->addElement('html',$return);
					}
				}
				
			}
			
			$form->addElement('submit', 'submit_button', get_lang('Ok'), 'style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; padding:1px 2px; width:75px;"');
			
			if($action == 'move')
			{
				$form->addElement('hidden', 'title', $item_title);
				$form->addElement('hidden', 'description', $item_description);
			}
			if(is_numeric($extra_info))
			{
				$form->addElement('hidden', 'path', $extra_info);
			}
			elseif(is_array($extra_info))
			{
				$form->addElement('hidden', 'path', $extra_info['path']);
			}
			
			$form->addElement('hidden', 'type', TOOL_DOCUMENT);
			$form->addElement('hidden', 'post_time', time());
			
		$form->addElement('html','</div>');
		
		$form->setDefaults($defaults);
		
		return $form->return_form();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $action
	 * @param unknown_type $id
	 * @param unknown_type $extra_info
	 */
	function display_link_form($action = 'add', $id = 0, $extra_info = '')
	{
		$tbl_lp_item = Database::get_course_table('lp_item');
		$tbl_link = Database::get_course_table(TABLE_LINK);
		
		if($id != 0 && is_array($extra_info))
		{
			$item_title			= stripslashes($extra_info['title']);
			$item_description	= stripslashes($extra_info['description']);
		}
		elseif(is_numeric($extra_info))
		{
			$sql_link = "
				SELECT
					title,
					description,
					url
				FROM " . $tbl_link . "
				WHERE id = " . $extra_info;
			
			$result = api_sql_query($sql_link, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			
			$item_title = $row['title'];
			$item_description = $row['description'];
			$item_url = $row['url'];
		}
		else
		{
			$item_title			= '';
			$item_description	= '';
		}
				
		$return = '<div style="margin:3px 10px;">';
			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			$arrLP = array();
			
			while($row = Database::fetch_array($result))
			{
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
					'prerequisite' => $row['prerequisite']);
			}
			
			$this->tree_array($arrLP);
			
			$arrLP = $this->arrMenu;
			
			unset($this->arrMenu);
			
			if($action == 'add')
				$return .= '<p class="lp_title">'.get_lang("CreateTheLink").' :</p>' . "\n";
			elseif($action == 'move')
				$return .= '<p class="lp_title">'.get_lang("MoveCurrentLink").' :</p>' . "\n";
			else
				$return .= '<p class="lp_title">'.get_lang("EditCurrentLink").' :</p>' . "\n";
			
			$return .= '<form method="POST">' . "\n";
			
				$return .= "\t" . '<table cellpadding="0" cellspacing="0" class="lp_form">' . "\n";
				
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td class="label"><label for="idParent">'.get_lang("Parent").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idParent" name="parent" onchange="load_cbo(this.value);" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">' . $this->name . '</option>';
								
								$arrHide = array($id);
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($action != 'add')
									{
										if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide))
										{
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '</option>';
										}
										else
										{
											$arrHide[] = $arrLP[$i]['id'];
										}
									}
									else
									{
										if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '</option>';
									}
								}
								
								reset($arrLP);
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
									
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idPosition">'.get_lang("Position").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idPosition" name="previous" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">'.get_lang("FirstPosition").'</option>';
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
									{
										if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
											$selected = 'selected="selected" ';
										elseif($action == 'add')
											$selected = 'selected="selected" ';
										else
											$selected = '';
										
										$return .= "\t\t\t\t\t" . '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">'.get_lang("After").' "' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '"</option>';
									}
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					if($action != 'move')
					{
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idTitle">'.get_lang("Title").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input id="idTitle" name="title" type="text" value="' . $item_title . '" /></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idDescription">'.get_lang("Description").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><textarea id="idDescription" name="description" rows="4">' . $item_description . '</textarea></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idURL">'.get_lang("Url").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input' . (is_numeric($extra_info) ? ' disabled="disabled"' : '') . ' id="idURL" name="url" type="text" value="' . $item_url . '" /></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$id_prerequisite=0;
						foreach($arrLP as $key=>$value){
							if($value['id']==$id){
								$id_prerequisite=$value['prerequisite'];
								break;
							}
						}
						
						$arrHide=array();
						for($i = 0; $i < count($arrLP); $i++)
						{
							if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter')
							{
								if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
									$s_selected_position=$arrLP[$i]['id'];
								elseif($action == 'add')
									$s_selected_position=0;
								$arrHide[$arrLP[$i]['id']]['value']=html_entity_decode(stripslashes($arrLP[$i]['title']));
								
							}
						}
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idPrerequisites">'.get_lang("Prerequisites").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><select name="prerequisites" id="prerequisites" style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; width:300px;"><option value="0">'.get_lang("NoPrerequisites").'</option>';
							
							foreach($arrHide as $key => $value){
								if($key==$s_selected_position && $action == 'add'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								elseif($key==$id_prerequisite && $action == 'edit'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								else{
									$return .= '<option value="'.$key.'">'.$value['value'].'</option>';
								}
							}
							
							$return .= "</select></td>";
						
						$return .= "\t\t" . '</tr>' . "\n";
								
					}
					
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td colspan="2"><input class="button" name="submit_button" type="submit" value="'.get_lang("Ok").'" /></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
				
				$return .= "\t" . '</table>' . "\n";	
				
				if($action == 'move')
				{
					$return .= "\t" . '<input name="title" type="hidden" value="' . $item_title . '" />' . "\n";
					$return .= "\t" . '<input name="description" type="hidden" value="' . $item_description . '" />' . "\n";
				}
				
				if(is_numeric($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info . '" />' . "\n";
				}
				elseif(is_array($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />' . "\n";
				}
				
				$return .= "\t" . '<input name="type" type="hidden" value="'.TOOL_LINK.'" />' . "\n";
				$return .= "\t" . '<input name="post_time" type="hidden" value="' . time() . '" />' . "\n";
				
			$return .= '</form>' . "\n";
		
		$return .= '</div>' . "\n";
		
		return $return;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $action
	 * @param unknown_type $id
	 * @param unknown_type $extra_info
	 * @return unknown
	 */
	function display_student_publication_form($action = 'add', $id = 0, $extra_info = '')
	{
		$tbl_lp_item = Database::get_course_table('lp_item');
		$tbl_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
		
		if($id != 0 && is_array($extra_info))
		{
			$item_title			= stripslashes($extra_info['title']);
			$item_description	= stripslashes($extra_info['description']);
		}
		elseif(is_numeric($extra_info))
		{
			$sql_publication = "
				SELECT
					title,
					description
				FROM " . $tbl_publication . "
				WHERE id = " . $extra_info;
			
			$result = api_sql_query($sql_publication, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			
			$item_title = $row['title'];
		}
		else
		{
			$item_title			= '';
		}
				
		$return = '<div style="margin:3px 10px;">';
			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			$arrLP = array();
			
			while($row = Database::fetch_array($result))
			{
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
					'prerequisite' => $row['prerequisite']);
			}
			
			$this->tree_array($arrLP);
			
			$arrLP = $this->arrMenu;
			
			unset($this->arrMenu);
			
			if($action == 'add')
				$return .= '<p class="lp_title">'.get_lang("Student_publication").' :</p>' . "\n";
			elseif($action == 'move')
				$return .= '<p class="lp_title">'.get_lang("MoveCurrentStudentPublication").' :</p>' . "\n";
			else
				$return .= '<p class="lp_title">'.get_lang("EditCurrentStudentPublication").' :</p>' . "\n";
			
			$return .= '<form method="POST">' . "\n";
			
				$return .= "\t" . '<table cellpadding="0" cellspacing="0" class="lp_form">' . "\n";
				
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td class="label"><label for="idParent">'.get_lang("Parent").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idParent" name="parent" onchange="load_cbo(this.value);" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">' . $this->name . '</option>';
								
								$arrHide = array($id);
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($action != 'add')
									{
										if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide))
										{
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '</option>';
										}
										else
										{
											$arrHide[] = $arrLP[$i]['id'];
										}
									}
									else
									{
										if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '</option>';
									}
								}
								
								reset($arrLP);
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
									
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idPosition">'.get_lang("Position").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idPosition" name="previous" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">'.get_lang("FirstPosition").'</option>';
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
									{
										if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
											$selected = 'selected="selected" ';
										elseif($action == 'add')
											$selected = 'selected="selected" ';
										else
											$selected = '';
										
										$return .= "\t\t\t\t\t" . '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">'.get_lang("After").' "' . html_entity_decode(stripslashes($arrLP[$i]['title'])) . '"</option>';
									}
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					if($action != 'move')
					{
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idTitle">'.get_lang("Title").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input id="idTitle" name="title" type="text" value="' . $item_title . '" /></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$id_prerequisite=0;
						foreach($arrLP as $key=>$value){
							if($value['id']==$id){
								$id_prerequisite=$value['prerequisite'];
								break;
							}
						}
						
						$arrHide=array();
						for($i = 0; $i < count($arrLP); $i++)
						{
							if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter')
							{
								if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
									$s_selected_position=$arrLP[$i]['id'];
								elseif($action == 'add')
									$s_selected_position=0;
								$arrHide[$arrLP[$i]['id']]['value']=html_entity_decode(stripslashes($arrLP[$i]['title']));
								
							}
						}
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idPrerequisites">'.get_lang("Prerequisites").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><select name="prerequisites" id="prerequisites" style="background:#F8F8F8; border:1px solid #999999; font-family:Arial, Verdana, Helvetica, sans-serif; font-size:12px; width:300px;"><option value="0">'.get_lang("NoPrerequisites").'</option>';
							
							foreach($arrHide as $key => $value){
								if($key==$s_selected_position && $action == 'add'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								elseif($key==$id_prerequisite && $action == 'edit'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								else{
									$return .= '<option value="'.$key.'">'.$value['value'].'</option>';
								}
							}
							
							$return .= "</select></td>";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
					}
					
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td colspan="2"><input class="button" name="submit_button" type="submit" value="'.get_lang("Ok").'" /></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
				
				$return .= "\t" . '</table>' . "\n";	
				
				if($action == 'move')
				{
					$return .= "\t" . '<input name="title" type="hidden" value="' . $item_title . '" />' . "\n";
					$return .= "\t" . '<input name="description" type="hidden" value="' . $item_description . '" />' . "\n";
				}
				
				if(is_numeric($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info . '" />' . "\n";
				}
				elseif(is_array($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />' . "\n";
				}
				
				$return .= "\t" . '<input name="type" type="hidden" value="'.TOOL_STUDENTPUBLICATION.'" />' . "\n";
				$return .= "\t" . '<input name="post_time" type="hidden" value="' . time() . '" />' . "\n";
				
			$return .= '</form>' . "\n";
		
		$return .= '</div>' . "\n";
		
		return $return;
	}
	
	/**
	 * Displays the menu for manipulating a step
	 *
	 * @return unknown
	 */
	function display_manipulate($item_id, $item_type = TOOL_DOCUMENT)
	{
		$return = '<div class="lp_manipulate"><table border="0" width="100%"><tr><td valign="top" width="400">';
		
		switch($item_type)
		{
			case 'dokeos_chapter':
			case 'chapter':
				
				$lang = get_lang('TitleManipulateChapter');
				break;
				
			case 'dokeos_module':
			case 'module':
				
				$lang = get_lang('TitleManipulateModule');
				
				break;
				
			case TOOL_DOCUMENT:
				
				$lang = get_lang('TitleManipulateDocument');
				
				break;
			
			case TOOL_LINK:
			case 'link':
				
				$lang = get_lang('TitleManipulateLink');
				
				break;
			
			case TOOL_QUIZ:
				
				$lang = get_lang('TitleManipulateQuiz');
				
				break;
			
			case TOOL_STUDENTPUBLICATION:
				
				$lang = get_lang('TitleManipulateStudentPublication');
				
				break;
		}
		
		$tbl_lp_item	= Database::get_course_table('lp_item');
		
		$sql = "
			SELECT
				description 
			FROM " . $tbl_lp_item . " as lp
			WHERE
				lp.id = " . $item_id;

		$result = api_sql_query($sql, __FILE__, __LINE__);
		
		$s_description=mysql_result($result,0,0);
		
		$return .= '<p class="lp_title">' . $lang . '</p>';
		
		$return .= '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=edit_item&amp;view=build&amp;id=' . $item_id . '&amp;lp_id=' . $this->lp_id . '" title="Edit the current item"><img align="absbottom" alt="Edit the current item" src="../img/edit.gif" title="Edit the current item" /> '.get_lang("Edit").'</a>';
		$return .= '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=move_item&amp;view=build&amp;id=' . $item_id . '&amp;lp_id=' . $this->lp_id . '" title="Move the current item"><img align="absbottom" alt="Move the current item" src="../img/deplacer_fichier.gif" title="Move the current item" /> '.get_lang("Move").'</a>';
		$return .= '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=edit_item_prereq&amp;view=build&amp;id=' . $item_id . '&amp;lp_id=' . $this->lp_id . '" title="'.get_lang('Prerequisites').'"><img align="absbottom" alt="'.get_lang('Prerequisites').'" src="../img/right.gif" title="'.get_lang('Prerequisites').'" /> '.get_lang('Prerequisites').'</a>';
		$return .= '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=delete_item&amp;view=build&amp;id=' . $item_id . '&amp;lp_id=' . $this->lp_id . '" onclick="return confirmation(\'' . $row['title'] . '\');" title="Delete the current item"><img alt="Delete the current item" align="absbottom" src="../img/delete.gif" title="Delete the current item" /> '.get_lang("Delete").'</a>';
		
		//$return .= '<br><br><p class="lp_text">' . ((trim($s_description) == '') ? ''.get_lang("NoDescription").'' : stripslashes(nl2br($s_description))) . '</p>';
		
		$return.="</td><td valign='top'>";
		
		// get the audiorecorder. Use of ob_* functions since there are echos in the file
		ob_start();
		$audio_recorder_studentview = 'false';
		$audio_recorder_item_id = $item_id;
		if(api_get_setting('service_ppt2lp','active')=='true' && api_get_setting('service_ppt2lp','path_to_lzx')!=''){
			include('audiorecorder.inc.php');
		}
		$return .= ob_get_contents();
		ob_end_clean();
		// end of audiorecorder include
		
		$return.="</td></tr></table>";
		
		$return .= '</div>';
		

		return $return;
	}
	
	/**
	 * Creates the javascript needed for filling up the checkboxes without page reload
	 *
	 * @return string
	 */
	function create_js()
	{
		$return = '<script language="javascript" type="text/javascript">' . "\n";
		
		$return .= 'function load_cbo(id){' . "\n";
		
		$return .= "var cbo = document.getElementById('idPosition');";
		
		$return .= 'for(var i = cbo.length - 1; i > 0; i--)';
			$return .= 'cbo.options[i] = null;';
		
		$return .= 'for(var i = 1; i <= child_name[id].length; i++)';
			$return .= 'cbo.options[i] = new Option(child_name[id][i-1], child_value[id][i-1]);';
		
		$return .= '}' . "\n\n";
		
		$return .= 'var child_name = new Array();' . "\n";
		$return .= 'var child_value = new Array();' . "\n\n";
		
		$return .= 'child_name[0] = new Array();' . "\n";
		$return .= 'child_value[0] = new Array();' . "\n\n";
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		
		$sql_zero = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE
				lp_id = " . $this->lp_id . " AND
				parent_item_id = 0
			ORDER BY display_order ASC";
		$res_zero = api_sql_query($sql_zero, __FILE__, __LINE__);
		
		$i = 0;
		
		while($row_zero = Database::fetch_array($res_zero))
		{
			$return .= 'child_name[0][' . $i . '] = "'.get_lang("After").' \"' . $row_zero['title'] . '\"";' . "\n";
			$return .= 'child_value[0][' . $i++ . '] = "' . $row_zero['id'] . '";' . "\n";
		}
		
		$return .= "\n";
		
		$sql = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE
				lp_id = " . $this->lp_id;
		$res = api_sql_query($sql, __FILE__, __LINE__);
		
		while($row = Database::fetch_array($res))
		{
			$sql_parent = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE parent_item_id = " . $row['id'] . "
				ORDER BY display_order ASC";
			$res_parent = api_sql_query($sql_parent, __FILE__, __LINE__);
			
			$i = 0;
			
			$return .= 'child_name[' . $row['id'] . '] = new Array();' . "\n";
			$return .= 'child_value[' . $row['id'] . '] = new Array();' . "\n\n";
			
			while($row_parent = Database::fetch_array($res_parent))
			{
				$return .= 'child_name[' . $row['id'] . '][' . $i . '] = "'.get_lang("After").' \"' . $row_parent['title'] . '\"";' . "\n";
				$return .= 'child_value[' . $row['id'] . '][' . $i++ . '] = "' . $row_parent['id'] . '";' . "\n";
			}
			
			$return .= "\n";
		}
		
		$return .= '</script>' . "\n";
		
		return $return;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $item_id
	 * @return unknown
	 */
	function display_move_item($item_id)
	{
		global $_course; //will disappear
		
		$return = '';
		
		if(is_numeric($item_id))
		{
			$tbl_lp_item = Database::get_course_table('lp_item');
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE id = " . $item_id;
			
			$res = api_sql_query($sql, __FILE__, __LINE__);
			$row = Database::fetch_array($res);
			
			switch($row['item_type'])
			{
				case 'dokeos_chapter':
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_item_form($row['item_type'], 'Move the current chapter:', 'move', $item_id, $row);
					
					break;
				
				case 'dokeos_module':
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_item_form($row['item_type'], 'Move th current module:', 'move', $item_id, $row);
					
					break;
				
				case TOOL_DOCUMENT:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_document_form('move', $item_id, $row);
					
					break;
			
				case TOOL_LINK:
					
			$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_link_form('move', $item_id, $row);
					
					break;
					
				case TOOL_QUIZ:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_quiz_form('move', $item_id, $row);
					
					break;
				
				case TOOL_STUDENTPUBLICATION:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_student_publication_form('move', $item_id, $row);
					
					break;
			}
		}
		
		return $return;
	}
	
	/**
	 * Displays a basic form on the overview page for changing the item title and the item description.
	 *
	 * @param string $item_type
	 * @param string $title
	 * @param array $data
	 * @return string
	 */
	function display_item_small_form($item_type, $title = '', $data)
	{
		$return .= '<div class="lp_small_form">' . "\n";
						
			$return .= '<p class="lp_title">' . $title . '</p>';
			
			$return .= '<form method="post">' . "\n";
				
				$return .= '<table cellpadding="0" cellspacing="0" class="lp_form">';
				
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idTitle">Title:</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input"><input class="small_form" id="idTitle" name="title" type="text" value="' . $data['title'] . '" /></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idDescription">Description:</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input"><textarea class="small_form" id="idDescription" name="description" rows="4">' . $data['description'] . '</textarea></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td colspan="2"><input class="button small_form" name="submit_button" type="submit" value="OK" /></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
				$return .= "\t\t" . '</table>' . "\n";
				
				$return .= "\t" . '<input name="parent" type="hidden" value="' . $data['parent_item_id'] . '"/>' . "\n";
				$return .= "\t" . '<input name="previous" type="hidden" value="' . $data['previous_item_id'] . '"/>' . "\n";
				
			$return .= '</form>';
		
		$return .= '</div>';
		
		return $return;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $item_id
	 * @return unknown
	 */
	function display_item_prerequisites_form($item_id)
	{
		$tbl_lp_item = Database::get_course_table('lp_item');
		
		/* current prerequisite */
		$sql = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE id = " . $item_id;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$row = Database::fetch_array($result);
		
		$preq_id = $row['prerequisite'];
		$preq_min = $row['min_score'];
		$preq_max = $row['max_score'];
		
		$return = $this->display_manipulate($item_id, TOOL_DOCUMENT);
		
		$return .= '<div style="margin:3px 10px;">';
		
			$return .= '<p class="lp_title">'.get_lang("AddEditPrerequisites").'</p>';
			
			$return .= '<form method="POST">';
			
				$return .= '<table class="lp_form">';
				
					$return .= '<tr>';
					
						$return .= '<th></th>';
						$return .= '<th class="exercise">'.get_lang("Minimum").'</th>';
						$return .= '<th class="exercise">'.get_lang("Maximum").'</th>';
						
					$return .= '</tr>';
					
					$return .= '<tr>';
					
						$return .= '<td class="radio" colspan="3">';
						
							$return .= '<input checked="checked" id="idNone" name="prerequisites" style="margin-left:0; margin-right:10px;" type="radio" />';
							$return .= '<label for="idNone">'.get_lang("None").'</label>';
						
						$return .= '</td>';
					
					$return .= '</tr>';
					
					$sql = "
						SELECT *
						FROM " . $tbl_lp_item . "
						WHERE
							lp_id = " . $this->lp_id;
					
					$result = api_sql_query($sql, __FILE__, __LINE__);
					
					$arrLP = array();
					
					while($row = Database::fetch_array($result))
					{
						$arrLP[] = array(
							'id' => $row['id'],
							'item_type' => $row['item_type'],
							'title' => $row['title'],
							'description' => $row['description'],
							'parent_item_id' => $row['parent_item_id'],
							'previous_item_id' => $row['previous_item_id'],
							'next_item_id' => $row['next_item_id'],
							'max_score' => $row['max_score'],
							'min_score' => $row['min_score'],
							'next_item_id' => $row['next_item_id'],
							'display_order' => $row['display_order']);
					}
					
					$this->tree_array($arrLP);
					
					$arrLP = $this->arrMenu;
					
					unset($this->arrMenu);
					
					for($i = 0; $i < count($arrLP); $i++)
					{
						if($arrLP[$i]['id'] == $item_id)
							break;
						
						$return .= '<tr>';
							
							$return .= '<td class="radio"' . (($arrLP[$i]['item_type'] != TOOL_QUIZ) ? ' colspan="3"' : '') . '>';
							
								$return .= '<input' . (($arrLP[$i]['id'] == $preq_id) ? ' checked="checked" ' : '') . (($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter') ? ' disabled="disabled" ' : ' ') . 'id="id' . $arrLP[$i]['id'] . '" name="prerequisites" style="margin-left:' . $arrLP[$i]['depth'] * 10 . 'px; margin-right:10px;" type="radio" value="' . $arrLP[$i]['id'] . '" />';
								$return .= '<img alt="" src="../img/lp_' . $arrLP[$i]['item_type'] . '.png" style="margin-right:5px;" title="" />';
								$return .= '<label for="id' . $arrLP[$i]['id'] . '">' . stripslashes($arrLP[$i]['title']) . '</label>';
							
							$return .= '</td>';
							
							if($arrLP[$i]['item_type'] == TOOL_QUIZ)
							{
								$return .= '<td class="exercise">';
								
									$return .= '<input maxlength="3" name="min_' . $arrLP[$i]['id'] . '" type="text" value="' . (($arrLP[$i]['id'] == $preq_id) ? $preq_min : 0) . '" />';
								
								$return .= '</td>';
								
								$return .= '<td class="exercise">';
								
									$return .= '<input maxlength="3" name="max_' . $arrLP[$i]['id'] . '" type="text" value="' . $arrLP[$i]['max_score'] . '" disabled="true" />';
								
								$return .= '</td>';
							}
							
						$return .= '</tr>';
					}
			
					$return .= '<tr>';
							
						$return .= '<td colspan="3">';
						
							$return .= '<input class="button" name="submit_button" type="submit" value="'.get_lang("Ok").'" /></td>' . "\n";
						
						$return .= '</td>';
							
					$return .= '</tr>';
				
				$return .= '</table>';
			
			$return .= '</form>';
			
		$return .= '</div>';
		
		return $return;
	}
	
	/**
	 * Creates a list with all the documents in it
	 *
	 * @return string
	 */
	function get_documents()
	{
		global $_course;
		
		$tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
		
		$sql_doc = "
			SELECT *
			FROM " . $tbl_doc . "
			WHERE
				path NOT LIKE '%_DELETED_%' 
			ORDER BY path ASC";
		$res_doc = api_sql_query($sql_doc, __FILE__, __LINE__);
		
		$return = '<div class="lp_resource_header"' . " onclick=\"if(document.getElementById('resDoc').style.display == 'block') {document.getElementById('resDoc').style.display = 'none';} else {document.getElementById('resDoc').style.display = 'block';}\"" . ' style="cursor:pointer;"><img align="left" alt="" src="../img/lp_' . TOOL_DOCUMENT . '.gif" style="margin-right:5px;" title="" />'.get_lang("Document").'</div>';
		$return .= '<div class="lp_resource_elements" id="resDoc">';
		
		$resources=api_store_result($res_doc);
		
		$return .=$this->write_resources_tree('', $resources);
		
		$return .='</div>';

		if(Database::num_rows($res_doc) == 0)
			$return .= '<div class="lp_resource_element">'.get_lang("NoDocuments").'</div>';
		

		return $return;
	}
	
	function write_resources_tree($parent, $resources_array_first = false){
		
		include_once(api_get_path(LIBRARY_PATH).'fileDisplay.lib.php');
		static $resources_array;
		if($resources_array_first !== false)
			$resources_array = $resources_array_first;
		
	
		while($value = current($resources_array))
		{
			
			if(strpos($value['path'], $parent)!==false || $parent=='')
			{

				$explode = explode('/', $value['path']);
				$num = count($explode) - 2;
								
				//It's a file
				if ($value['filetype'] == 'file') {
					if($num==0) $num=1;
					
					$icon = choose_image(trim($value['path']));
					$position = strrpos($icon,'.');
					$icon=substr($icon,0,$position).'_small.gif';
					
					//value['path'] don't always have an extension so we must take the path to have the complete name with extension
					$array_temp = explode('/',trim($value['path']));
					$document_name = $array_temp[count($array_temp)-1];
					
					$return .= '<div><div style="margin-left:' . ($num * 15) . 'px;margin-right:5px;"><a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_DOCUMENT . '&amp;file=' . $value['id'] . '&amp;lp_id=' . $this->lp_id . '"><img align="left" alt="" src="../img/'.$icon.'" title="" />'.$document_name."</a></div></div>\r\n";
					array_shift($resources_array);
				}
				//It's a folder
				else {
					$return .= '<div><div style="margin-left:' . ($num * 15) . 'px;margin-right:5px;"><img style="cursor: pointer;" src="../img/nolines_plus.gif" align="absmiddle" id="img_'.$value["id"].'" onclick="testResources(\''.$value["id"].'\',\'img_'.$value["id"].'\')"><img alt="" src="../img/lp_' . (($value['filetype'] == 'file') ? TOOL_DOCUMENT.'_file' : 'folder') . '.gif" title="" align="absmiddle" /><span onclick="testResources(\''.$value["id"].'\',\'img_'.$value["id"].'\')" style="cursor: pointer;" >'.$value['title'].'</span></div><div style="display: none;" id="'.$value['id'].'">';
					array_shift($resources_array);
					$return .= $this->write_resources_tree($value['path']);
					$return .= "</div></div>\r\n";
				}
			}
			else
				return $return;
		}
		return $return;
	}
	
	/**
	 * Creates a list with all the exercises (quiz) in it
	 *
	 * @return string
	 */
	function get_exercises()
	{
		$tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
			
		$sql_quiz = "
			SELECT *
			FROM " . $tbl_quiz . "
			WHERE active<>'-1'
			ORDER BY title ASC";
		$res_quiz = api_sql_query($sql_quiz, __FILE__, __LINE__);
		
		$return .= '<div class="lp_resource_header_end"' . " onclick=\"if(document.getElementById('resExercise').style.display == 'block') {document.getElementById('resExercise').style.display = 'none';} else {document.getElementById('resExercise').style.display = 'block';}\"" . ' style="cursor:pointer;"><img align="left" alt="" src="../img/lp_' . TOOL_QUIZ . '.gif" style="margin-right:5px;" title="" />'.get_lang("Exercise").'</div>';
		$return .= '<div class="lp_resource_elements_end" id="resExercise">';
		
			while($row_quiz = Database::fetch_array($res_quiz))
			{
				$return .= '<div class="lp_resource_element">';
				
					$return .= '<img alt="" src="../img/quizz_small.gif" style="margin-right:5px;" title="" />';
					$return .= '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_QUIZ . '&amp;file=' . $row_quiz['id'] . '&amp;lp_id=' . $this->lp_id . '">' . $row_quiz['title'] . '</a>';
					//$return .= $row_quiz['title'];
				
				$return .= '</div>';
			}
			
			if(Database::num_rows($res_quiz) == 0)
				$return .= '<div class="lp_resource_element">'.get_lang("NoExercisesAvailable").'</div>';
		
		$return .= '</div>';
		
		return $return;
	}
	
	/**
	 * Creates a list with all the links in it
	 *
	 * @return string
	 */
	function get_links()
	{
		$tbl_link = Database::get_course_table(TABLE_LINK);
			
		$sql_link = "
			SELECT *
			FROM " . $tbl_link . "
			ORDER BY title ASC";
		$res_link = api_sql_query($sql_link, __FILE__, __LINE__);
		
		$return .= '<div class="lp_resource_header"' . " onclick=\"if(document.getElementById('resLink').style.display == 'block') {document.getElementById('resLink').style.display = 'none';} else {document.getElementById('resLink').style.display = 'block';}\"" . ' style="cursor:pointer;"><img align="left" alt="" src="../img/lp_' . TOOL_LINK . '.gif" style="margin-right:5px;" title="" />'.get_lang("Links").'</div>';
		$return .= '<div class="lp_resource_elements" id="resLink">';
		
			while($row_link = Database::fetch_array($res_link))
			{
				$return .= '<div class="lp_resource_element">';
				
					$return .= '<img align="left" alt="" src="../img/file_html_small.gif" style="margin-right:5px;" title="" />';
					$return .= '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_LINK . '&amp;file=' . $row_link['id'] . '&amp;lp_id=' . $this->lp_id . '">' . $row_link['title'] . '</a>';
				
				$return .= '</div>';
			}
			
			if(Database::num_rows($res_link) == 0)
				$return .= '<div class="lp_resource_element">'.get_lang("NoLinksAvailable").'</div>';
		
		$return .= '</div>';
		
		return $return;
	}
	
	/**
	 * Creates a list with all the student publications in it
	 *
	 * @return unknown
	 */
	function get_student_publications()
	{
		$tbl_student = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
			
		$sql_student = "
			SELECT *
			FROM " . $tbl_student . "
			ORDER BY title ASC";
		$res_student = api_sql_query($sql_student, __FILE__, __LINE__);
		
		$return .= '<div class="lp_resource_header"' . " onclick=\"if(document.getElementById('resStudent').style.display == 'block') {document.getElementById('resStudent').style.display = 'none';} else {document.getElementById('resStudent').style.display = 'block';}\"" . ' style="border-bottom:1px solid #999999; cursor:pointer;"><img align="left" alt="" src="../img/lp_' . TOOL_STUDENTPUBLICATION . '.gif" style="margin-right:5px;" title="" />'.get_lang('Assignments').'</div>';
		$return .= '<div class="lp_resource_elements" id="resStudent" style="border-bottom:1px solid #999999; border-top:0;">';
		$return .= '<div class="lp_resource_element">';
		$return .= '<img align="left" alt="" src="../img/works_small.gif" style="margin-right:5px;" title="" />';
		$return .= '<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_STUDENTPUBLICATION . '&amp;lp_id=' . $this->lp_id . '">' . get_lang('AddAssignmentPage') . '</a>';
		$return .= '</div>';
		$return .= '</div>';
		
		return $return;
	}
	
	function get_forums()
	{
		include ('../forum/forumfunction.inc.php');
		include ('../forum/forumconfig.inc.php');
		global $table_forums, $table_threads,$table_posts, $table_item_property, $table_users;
		$table_forums = Database :: get_course_table(TABLE_FORUM);
		$table_threads = Database :: get_course_table(TABLE_FORUM_THREAD);
		$table_posts = Database :: get_course_table(TABLE_FORUM_POST);
		$table_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
		$table_users = Database :: get_main_table(TABLE_MAIN_USER);
		$a_forums = get_forums();
		
		$return .= '<div class="lp_resource_header"' . " onclick=\"if(document.getElementById('forums').style.display == 'block') {document.getElementById('forums').style.display = 'none';} else {document.getElementById('forums').style.display = 'block';}\"" . ' style="border-bottom:1px solid #999999; cursor:pointer;"><img align="left" alt="" src="../img/lp_forum.gif" style="margin-right:5px;" title="" />'.get_lang('Forums').'</div>';
		$return .= '<div class="lp_resource_elements" id="forums" style="border-bottom:1px solid #999999; border-top:0;">';
		
		foreach($a_forums as $forum)
		{
			$return .= '<div class="lp_resource_element">';
			$return .= '<script type="text/javascript">
						function toggle_forum(forum_id){
							if(document.getElementById("forum_"+forum_id+"_content").style.display == "none"){
								document.getElementById("forum_"+forum_id+"_content").style.display = "block";
								document.getElementById("forum_"+forum_id+"_opener").src = "'.api_get_path(WEB_IMG_PATH).'remove.gif";
							}
							else {
								document.getElementById("forum_"+forum_id+"_content").style.display = "none";
								document.getElementById("forum_"+forum_id+"_opener").src = "'.api_get_path(WEB_IMG_PATH).'add.gif";
							}
						}
						</script>
						';
			$return .= '<img align="left" alt="" src="../img/lp_forum.gif" style="margin-right:5px;" title="" />';
			$return .= '<a style="cursor:hand" onclick="toggle_forum('.$forum['forum_id'].')" style="vertical-align:middle"><img src="'.api_get_path(WEB_IMG_PATH).'add.gif" id="forum_'.$forum['forum_id'].'_opener" align="absbottom" /></a>
						<a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_FORUM . '&amp;forum_id=' . $forum['forum_id'] . '&amp;lp_id=' . $this->lp_id . '" style="vertical-align:middle">' . $forum['forum_title'] . '</a><ul style="display:none" id="forum_'.$forum['forum_id'].'_content">';
			$a_threads = get_threads($forum['forum_id']);
			foreach($a_threads as $thread)
			{
				$return .=  '<li><a href="' . $_SERVER['PHP_SELF'] . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_THREAD . '&amp;thread_id=' . $thread['thread_id'] . '&amp;lp_id=' . $this->lp_id . '">' . $thread['thread_title'] . '</a></li>';
			}
			$return .= '</ul></div>';
		}
		
		return $return;
	}
	
	/**
	 * Exports the learning path as a SCORM package. This is the main function that
	 * gathers the content, transforms it, writes the imsmanifest.xml file, zips the
	 * whole thing and returns the zip.
	 * 
	 * This method needs to be called in PHP5, as it will fail with non-adequate
	 * XML package (like the ones for PHP4), and it is *not* a static method, so
	 * you need to call it on a learnpath object.
	 * @TODO The method might be redefined later on in the scorm class itself to avoid
	 * creating a SCORM structure if there is one already. However, if the initial SCORM
	 * path has been modified, it should use the generic method here below.
	 * @TODO link this function with the export_lp() function in the same class
	 * @param	string	Optional name of zip file. If none, title of learnpath is
	 * 					domesticated and trailed with ".zip"
	 * @return	string	Returns the zip package string, or null if error 
	 */
	 function scorm_export()
	 {
	 	global $_course;
	 	if (!class_exists('DomDocument'))
	 	{
	 		error_log('DOM functions not supported for PHP version below 5.0',0);
			$this->error = 'PHP DOM functions not supported for PHP versions below 5.0';
			return null;
		}
	 	//Create the zip handler (this will remain available throughout the method)
		$temp_dir_short = uniqid();
		$temp_zip_dir = api_get_path(GARBAGE_PATH)."/".$temp_dir_short;
		$temp_zip_file = $temp_zip_dir."/".md5(time()).".zip";
		$zip_folder=new PclZip($temp_zip_file);
		$current_course_path = api_get_path(SYS_COURSE_PATH).api_get_course_path();
		$root_path = $main_path = api_get_path(SYS_PATH);
		//place to temporarily stash the zipfiles
		//create the temp dir if it doesn't exist
		//or do a cleanup befor creating the zipfile
		if(!is_dir($temp_zip_dir))
		{
			mkdir($temp_zip_dir);
		}
		else 
		{//cleanup: check the temp dir for old files and delete them
			$handle=opendir($temp_zip_dir);
			while (false!==($file = readdir($handle)))
			{
				if ($file != "." && $file != "..")
				{
					unlink("$temp_zip_dir/$file");
				}
			}
		    closedir($handle);
		}
		

	 	//Build a dummy imsmanifest structure. Do not add to the zip yet (we still need it)
	 	//This structure is developed following regulations for SCORM 1.2 packaging in the SCORM 1.2 Content
	 	//Aggregation Model official document, secion "2.3 Content Packaging"
	 	$xmldoc = new DOMDocument('1.0',$this->encoding);
	 	$root = $xmldoc->createElement('manifest');
	 	$root->setAttribute('identifier','SingleCourseManifest');
	 	$root->setAttribute('version','1.1');
	 	$root->setAttribute('xmlns','http://www.imsproject.org/xsd/imscp_rootv1p1p2');
	 	$root->setAttribute('xmlns:adlcp','http://www.adlnet.org/xsd/adlcp_rootv1p2');
	 	$root->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
	 	$root->setAttribute('xsi:schemaLocation','http://www.omsproject.org/xsd/imscp_rootv1p1p2 imscp_rootv1p1p2.xsd
	 			http://www.imsglobal.org/xsd/imsmd_rootv1p2p1 imsmd_rootv1p2p1.xsd
                http://www.adlnet.org/xsd/adlcp_rootv1p2 adlcp_rootv1p2.xsd');
	 	//Build mandatory sub-root container elements
	 	$metadata = $xmldoc->createElement('metadata');
	 	$md_schema = $xmldoc->createElement('schema','ADL SCORM');
	 	$metadata->appendChild($md_schema);
	 	$md_schemaversion = $xmldoc->createElement('schemaversion','1.2');
	 	$metadata->appendChild($md_schemaversion);
	 	$root->appendChild($metadata);
	 	
	 	$organizations = $xmldoc->createElement('organizations');
	 	
	 	$resources = $xmldoc->createElement('resources');
	 	
	 	//Build the only organization we will use in building our learnpaths
	 	$organizations->setAttribute('default','dokeos_scorm_export');
	 	$organization = $xmldoc->createElement('organization');
	 	$organization->setAttribute('identifier','dokeos_scorm_export');
	 	$org_title = $xmldoc->createElement('title',htmlspecialchars($this->get_name(),ENT_QUOTES)); //filter data for XML?
	 	$organization->appendChild($org_title);
	 	
	 	//For each element, add it to the imsmanifest structure, then add it to the zip.
	 	//Always call the learnpathItem->scorm_export() method to change it to the SCORM
	 	//format
	 	$zip_files = array();
	 	$zip_files_abs = array();
	 	$link_updates = array();
	 	foreach($this->items as $index => $item){
	 		
	 		if(!in_array($item->type , array(TOOL_QUIZ, TOOL_FORUM, TOOL_THREAD, TOOL_LINK)))
	 		{
		 		//get included documents from this item
		 		$inc_docs = $item->get_resources_from_source();
		 		//error_log('Dealing with document '.$item->get_file_path().', found included documents: '.print_r($inc_docs,true),0);
		 		//give a child element <item> to the <organization> element
		 		$my_item = $xmldoc->createElement('item');
		 		$my_item->setAttribute('identifier','ITEM_'.$item->get_id()); 
		 		$my_item->setAttribute('identifierref','RESOURCE_'.$item->get_id()); 
		 		$my_item->setAttribute('isvisible','true');
		 		//give a child element <title> to the <item> element
		 		$my_title = $xmldoc->createElement('title',htmlspecialchars($item->get_title(),ENT_QUOTES));
		 		$my_item->appendChild($my_title);
		 		//give a child element <adlcp:prerequisite> to the <item> element
		 		$my_prereqs = $xmldoc->createElement('adlcp:prerequisite',$item->get_prereq_string());
		 		$my_prereqs->setAttribute('type','aicc_script');
		 		$my_item->appendChild($my_prereqs);
		 		//give a child element <adlcp:maxtimeallowed> to the <item> element - not yet supported
		 		//$xmldoc->createElement('adlcp:maxtimeallowed','');
				//give a child element <adlcp:timelimitaction> to the <item> element - not yet supported
		 		//$xmldoc->createElement('adlcp:timelimitaction','');
		 		//give a child element <adlcp:datafromlms> to the <item> element - not yet supported
		 		//$xmldoc->createElement('adlcp:datafromlms','');
		 		//give a child element <adlcp:masteryscore> to the <item> element
		 		$my_masteryscore = $xmldoc->createElement('adlcp:masteryscore',$item->masteryscore);
		 		$my_item->appendChild($my_masteryscore);
		 		
		 		
		 		//attach this item to the organization element or hits parent if there is one
		 		if(!empty($item->parent) && $item->parent!=0)
		 		{
		 			$children = $organization->childNodes;
			        for($i=0;$i<$children->length;$i++){
				        $item_temp = $children->item($i);
				        if ($item_temp -> nodeName == 'item')
				        {
				        	if($item_temp->getAttribute('identifier') == 'ITEM_'.$item->parent)
				        	{
				        		$item_temp -> appendChild($my_item);
				        	}
				        	
				        }
			        }
		 		}
		 		else
		 		{
		 			$organization->appendChild($my_item);
		 		}
		 		
		 		
		 		//get the path of the file(s) from the course directory root
				$my_file_path = $item->get_file_path();
				$my_xml_file_path = htmlentities($my_file_path); 
				$my_sub_dir = dirname($my_file_path); 
				$my_xml_sub_dir = htmlentities($my_sub_dir);
		 		//give a <resource> child to the <resources> element
		 		$my_resource = $xmldoc->createElement('resource');
		 		$my_resource->setAttribute('identifier','RESOURCE_'.$item->get_id());
		 		$my_resource->setAttribute('type','webcontent');
		 		$my_resource->setAttribute('href',$my_xml_file_path);
		 		//adlcp:scormtype can be either 'sco' or 'asset'
		 		$my_resource->setAttribute('adlcp:scormtype','asset');
		 		//xml:base is the base directory to find the files declared in this resource
		 		$my_resource->setAttribute('xml:base','');
		 		//give a <file> child to the <resource> element
		 		$my_file = $xmldoc->createElement('file');
		 		$my_file->setAttribute('href',$my_xml_file_path);
		 		$my_resource->appendChild($my_file);
	
		 		//dependency to other files - not yet supported
		 		$i = 1;
		 		foreach($inc_docs as $doc_info)
		 		{
		 			if(count($doc_info)<1 or empty($doc_info[0])){continue;}
		 			$my_dep = $xmldoc->createElement('resource');
		 			$res_id = 'RESOURCE_'.$item->get_id().'_'.$i;
		 			$my_dep->setAttribute('identifier',$res_id);
		 			$my_dep->setAttribute('type','webcontent');
		 			$my_dep->setAttribute('adlcp:scormtype','asset');
		 			$my_dep_file = $xmldoc->createElement('file');
		 			//check type of URL
		 			//error_log('Now dealing with '.$doc_info[0].' of type '.$doc_info[1].'-'.$doc_info[2],0);
		 			if($doc_info[1] == 'remote')
		 			{ //remote file. Save url as is
		 				$my_dep_file->setAttribute('href',$doc_info[0]);
			 			$my_dep->setAttribute('xml:base','');
		 			}elseif($doc_info[1] == 'local'){
		 				switch($doc_info[2])
		 				{
		 					case 'url': //local URL - save path as url for now, don't zip file
				 				$my_dep_file->setAttribute('href',$doc_info[0]);
					 			$my_dep->setAttribute('xml:base','');
		 						break;
		 					case 'abs': //absolute path from DocumentRoot. Save file and leave path as is in the zip
				 				$my_dep_file->setAttribute('href',$doc_info[0]);
		 			 			$my_dep->setAttribute('xml:base','');
		 			 			$zip_files_abs[] = $doc_info[0];
	
			 					$current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
								$file_path = realpath($doc_info[0]);
			 					if(strstr($file_path,$main_path) !== false)
			 					{//the calculated real path is really inside the dokeos root path
			 						//reduce file path to what's under the DocumentRoot
			 						$file_path = substr($file_path,strlen($root_path));
			 						//error_log('Reduced path: '.$file_path,0);
			 						$zip_files_abs[] = $file_path;
			 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>$file_path);
					 				$my_dep_file->setAttribute('href','document/'.$file_path);
			 			 			$my_dep->setAttribute('xml:base','');
			 					}
		 						break;
		 					case 'rel': //path relative to the current document. Save xml:base as current document's directory and save file in zip as subdir.file_path
			 					if(substr($doc_info[0],0,2)=='..')
				 				{ //relative path going up
				 					$current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
				 					$file_path = realpath($current_dir.$doc_info[0]);
				 					//error_log($file_path.' <-> '.$main_path,0);
				 					if(strstr($file_path,$main_path) !== false)
				 					{//the calculated real path is really inside the dokeos root path
				 						//reduce file path to what's under the DocumentRoot
				 						$file_path = substr($file_path,strlen($root_path));
				 						//error_log('Reduced path: '.$file_path,0);
				 						$zip_files_abs[] = $file_path;
				 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>$file_path);
						 				$my_dep_file->setAttribute('href','document/'.$file_path);
				 			 			$my_dep->setAttribute('xml:base','');
				 					}
				 				}else{
				 					$zip_files[] = $my_sub_dir.'/'.$doc_info[0];
					 				$my_dep_file->setAttribute('href',$doc_info[0]);
			 			 			$my_dep->setAttribute('xml:base',$my_xml_sub_dir);
				 				}
		 						break;
		 					default:
				 				$my_dep_file->setAttribute('href',$doc_info[0]);
		 			 			$my_dep->setAttribute('xml:base','');
		 						break;
		 				}
		 			}
		 			$my_dep->appendChild($my_dep_file);
		 			$resources->appendChild($my_dep);
		 			$dependency = $xmldoc->createElement('dependency');
		 			$dependency->setAttribute('identifierref',$res_id);
		 			$my_resource->appendChild($dependency);
		 			$i++;
		 		}
		 		//$my_dependency = $xmldoc->createElement('dependency');
		 		//$my_dependency->setAttribute('identifierref','');
		 		$resources->appendChild($my_resource);
		 		
		 		$zip_files[] = $my_file_path;
				//error_log('File '.$my_file_path. ' added to $zip_files',0);
	 		}
	 		else
	 		{ // if the item is a quiz or a link or whatever non-exportable, we include a step indicating it
	 		
	 			$my_item = $xmldoc->createElement('item');
		 		$my_item->setAttribute('identifier','ITEM_'.$item->get_id()); 
		 		$my_item->setAttribute('identifierref','RESOURCE_'.$item->get_id()); 
		 		$my_item->setAttribute('isvisible','true');
		 		//give a child element <title> to the <item> element
		 		$my_title = $xmldoc->createElement('title',htmlspecialchars($item->get_title(),ENT_QUOTES));
		 		$my_item->appendChild($my_title);
		 		//give a child element <adlcp:prerequisite> to the <item> element
		 		$my_prereqs = $xmldoc->createElement('adlcp:prerequisite',$item->get_prereq_string());
		 		$my_prereqs->setAttribute('type','aicc_script');
		 		$my_item->appendChild($my_prereqs);
		 		//give a child element <adlcp:maxtimeallowed> to the <item> element - not yet supported
		 		//$xmldoc->createElement('adlcp:maxtimeallowed','');
				//give a child element <adlcp:timelimitaction> to the <item> element - not yet supported
		 		//$xmldoc->createElement('adlcp:timelimitaction','');
		 		//give a child element <adlcp:datafromlms> to the <item> element - not yet supported
		 		//$xmldoc->createElement('adlcp:datafromlms','');
		 		//give a child element <adlcp:masteryscore> to the <item> element
		 		$my_masteryscore = $xmldoc->createElement('adlcp:masteryscore',$item->masteryscore);
		 		$my_item->appendChild($my_masteryscore);
		 		
		 		
		 		//attach this item to the organization element or hits parent if there is one
		 		if(!empty($item->parent) && $item->parent!=0)
		 		{
		 			$children = $organization->childNodes;
			        for($i=0;$i<$children->length;$i++){
				        $item_temp = $children->item($i);
				        if ($item_temp -> nodeName == 'item')
				        {
				        	if($item_temp->getAttribute('identifier') == 'ITEM_'.$item->parent)
				        	{
				        		$item_temp -> appendChild($my_item);
				        	}
				        	
				        }
			        }
		 		}
		 		else
		 		{
		 			$organization->appendChild($my_item);
		 		}
		 		
		 		//get the path of the file(s) from the course directory root
				$my_file_path = 'non_exportable.html';
				$my_xml_file_path = htmlentities($my_file_path); 
				$my_sub_dir = dirname($my_file_path); 
				$my_xml_sub_dir = htmlentities($my_sub_dir);
		 		//give a <resource> child to the <resources> element
		 		$my_resource = $xmldoc->createElement('resource');
		 		$my_resource->setAttribute('identifier','RESOURCE_'.$item->get_id());
		 		$my_resource->setAttribute('type','webcontent');
		 		$my_resource->setAttribute('href','document/'.$my_xml_file_path);
		 		//adlcp:scormtype can be either 'sco' or 'asset'
		 		$my_resource->setAttribute('adlcp:scormtype','asset');
		 		//xml:base is the base directory to find the files declared in this resource
		 		$my_resource->setAttribute('xml:base','');
		 		//give a <file> child to the <resource> element
		 		$my_file = $xmldoc->createElement('file');
		 		$my_file->setAttribute('href','document/'.$my_xml_file_path);
		 		$my_resource->appendChild($my_file);
		 		$resources->appendChild($my_resource);
	 		}
	 	}
	 	$organizations->appendChild($organization);
	 	$root->appendChild($organizations);
	 	$root->appendChild($resources);
		$xmldoc->appendChild($root);

		//error_log(print_r($zip_files,true),0);
		$garbage_path = api_get_path(GARBAGE_PATH);
		$sys_course_path = api_get_path(SYS_COURSE_PATH);
		
		foreach($zip_files as $file_path)
		{
			if(empty($file_path)){continue;}
			//error_log('getting document from '.api_get_path('SYS_COURSE_PATH').$_course['path'].'/'.$file_path.' removing '.api_get_path('SYS_COURSE_PATH').$_course['path'].'/',0);
			$dest_file = $garbage_path.$temp_dir_short.'/'.$file_path;
			$this->create_path($dest_file);
			//error_log('copy '.api_get_path('SYS_COURSE_PATH').$_course['path'].'/'.$file_path.' to '.api_get_path('GARBAGE_PATH').$temp_dir_short.'/'.$file_path,0);
			//echo $main_path.$file_path.'<br>';
			copy($sys_course_path.$_course['path'].'/'.$file_path,$dest_file);
			//check if the file needs a link update
			if(in_array($file_path,array_keys($link_updates))){
				$string = file_get_contents($dest_file);
				unlink($dest_file);
				foreach($link_updates[$file_path] as $old_new)
				{
					//error_log('Replacing '.$old_new['orig'].' by '.$old_new['dest'].' in '.$file_path,0);
					$string = str_replace($old_new['orig'],$old_new['dest'],$string);
				}
				file_put_contents($dest_file,$string);
			}
			$zip_folder->add($dest_file,PCLZIP_OPT_REMOVE_PATH, $garbage_path);
		}
		foreach($zip_files_abs as $file_path)
		{
			if(empty($file_path)){continue;}
			//error_log('getting document from '.api_get_path('SYS_COURSE_PATH').$_course['path'].'/'.$file_path.' removing '.api_get_path('SYS_COURSE_PATH').$_course['path'].'/',0);
			$dest_file = $garbage_path.$temp_dir_short.'/document/'.$file_path;
			$this->create_path($dest_file);
			//error_log('Created path '.api_get_path('GARBAGE_PATH').$temp_dir_short.'/document/'.$file_path,0);
			//error_log('copy '.api_get_path('SYS_COURSE_PATH').$_course['path'].'/'.$file_path.' to '.api_get_path('GARBAGE_PATH').$temp_dir_short.'/'.$file_path,0);
			copy($main_path.$file_path,$dest_file);
			//check if the file needs a link update
			if(in_array($file_path,array_keys($link_updates))){
				$string = file_get_contents($dest_file);
				unlink($dest_file);
				foreach($link_updates[$file_path] as $old_new)
				{
					//error_log('Replacing '.$old_new['orig'].' by '.$old_new['dest'].' in '.$file_path,0);
					$string = str_replace($old_new['orig'],$old_new['dest'],$string);
				}
				file_put_contents($dest_file,$string);
			}
			$zip_folder->add($dest_file,PCLZIP_OPT_REMOVE_PATH, $garbage_path);
		}
		$lang_not_exportable = get_lang('ThisItemIsNotExportable');
		$file_content = 
<<<EOD
<html>
	<head>
		<style>
			.error-message {
				font-family: arial, verdana, helvetica, sans-serif;
				border-width: 1px;
				border-style: solid;
				left: 50%;
				margin: 10px auto;
				min-height: 30px;
				padding: 5px;
				right: 50%;
				width: 500px;
				background-color: #FFD1D1;
				border-color: #FF0000;
				color: #000;
			}
		</style>
	<body>
		<div class="error-message">
			$lang_not_exportable
		</div>
	</body>
</html>
EOD;
		file_put_contents($garbage_path.$temp_dir_short.'/document/non_exportable.html', $file_content);
		$zip_folder->add($garbage_path.$temp_dir_short.'/document/non_exportable.html',PCLZIP_OPT_REMOVE_PATH, $garbage_path);
		
	 	//Finalize the imsmanifest structure, add to the zip, then return the zip
	 	$xmldoc->save($garbage_path.'/'.$temp_dir_short.'/imsmanifest.xml');
		$zip_folder->add($garbage_path.'/'.$temp_dir_short.'/imsmanifest.xml',PCLZIP_OPT_REMOVE_PATH, $garbage_path.'/');

		//Send file to client
		$name = 'scorm_export_'.$this->lp_id.'.zip';
		DocumentManager::file_send_for_download($temp_zip_file,true,$name);
	}
	/**
	 * Temp function to be moved in main_api or the best place around for this. Creates a file path
	 * if it doesn't exist
	 */
	function create_path($path){
		$path_bits = split('/',dirname($path));
		$path_built = '/';
		foreach($path_bits as $bit){
			if(!empty($bit)){
				$new_path = $path_built.$bit;
				if(is_dir($new_path)){
					$path_built = $new_path.'/';
				}
				else
				{
					mkdir($new_path);
					$path_built = $new_path.'/';
				}
			}
		}
	}
}

?>