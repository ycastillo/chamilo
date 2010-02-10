<?php

class GlossaryManager {

    function __construct() {

    }
    /**
     * Get all glossary terms
     * @author Isaac Flores <isaac.flores@dokeos.com>
     * @return Array Contain glossary terms
     */
	public static function get_glossary_terms () {
		global $course;
		$glossary_id=array();
		$glossary_name=array();
		$glossary_desc=array();
		$glossary_table  = Database::get_course_table(TABLE_GLOSSARY);
		$sql='SELECT glossary_id as id,name,description FROM '.$glossary_table;
		$rs=Database::query($sql,__FILE__,__LINE__);
		while ($row=Database::fetch_array($rs)) {
			$glossary_data[]=$row;
		}
		return $glossary_data;
	}
	/**
	 * Get glossary term by glossary id
	 * @author Isaac Flores <florespaz@bidsoftperu.com>
	 * @param Integer The glossary id
	 * @return String The glossary description
	 */
	public static function get_glossary_term_by_glossary_id ($glossary_id) {
		global $course;
		$glossary_table  = Database::get_course_table(TABLE_GLOSSARY);
		$sql='SELECT description FROM '.$glossary_table.' WHERE glossary_id="'.Database::escape_string($glossary_id).'"';
		$rs=Database::query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($rs);
		return $row['description'];
	}
	/**
	 * Get glossary term by glossary id
	 * @author Isaac Flores <florespaz_isaac@hotmail.com>
	 * @param String The glossary term name
	 * @return String The glossary description
	 */
	public static function get_glossary_term_by_glossary_name ($glossary_name) {
		global $course;
		$glossary_table  = Database::get_course_table(TABLE_GLOSSARY);
		$sql='SELECT description FROM '.$glossary_table.' WHERE name like trim("'.Database::escape_string($glossary_name).'") ';
		$rs=Database::query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($rs);
		return $row['description'];
	}
	/**
	 * This functions stores the glossary in the database
	 *
	 * @param unknown_type $values
	 *
	 * @author Christian Fasanando <christian.fasanando@dokeos.com>
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function save_glossary($values)
	{
		// Database table definition
		$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
	
		// get the maximum display order of all the glossary items
		$max_glossary_item = GlossaryManager::get_max_glossary_item();
	
		// session_id
		$session_id = api_get_session_id();
	
		// check if the glossary term already exists
		if (GlossaryManager::glossary_exists($values['glossary_title']))
		{
			// display the feedback message
			Display::display_error_message(get_lang('GlossaryTermAlreadyExistsYouShouldEditIt'));
		} else {
			$sql = "INSERT INTO $t_glossary (name, description, display_order, session_id)
					VALUES(
						'".Database::escape_string(Security::remove_XSS($values['glossary_title']))."',
						'".Database::escape_string(Security::remove_XSS(stripslashes(api_html_entity_decode($values['glossary_comment'])),COURSEMANAGERLOWSECURITY))."',
						'".(int)($max_glossary_item + 1)."',
						'".Database::escape_string($session_id)."'
						)";
			$result = Database::query($sql, __FILE__, __LINE__);
			$id = Database::insert_id();
			if ($id>0) {
				//insert into item_property
				api_item_property_update(api_get_course_info(), TOOL_GLOSSARY, $id, 'GlossaryAdded', api_get_user_id());
			}
			$_SESSION['max_glossary_display'] = GlossaryManager::get_max_glossary_item();
			// display the feedback message
			Display::display_confirmation_message(get_lang('TermAdded'));
		}
	}
	
	/**
	 * update the information of a glossary term in the database
	 *
	 * @param array $values an array containing all the form elements
	 *
	 * @author Christian Fasanando <christian.fasanando@dokeos.com>
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function update_glossary($values)
	{
		// Database table definition
		$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
	
	
		// check if the glossary term already exists
		if (GlossaryManager::glossary_exists($values['glossary_title'],$values['glossary_id']))
		{
			// display the feedback message
			Display::display_error_message(get_lang('GlossaryTermAlreadyExistsYouShouldEditIt'));
		}
		else
		{
			$sql = "UPDATE $t_glossary SET
							name 		= '".Database::escape_string(Security::remove_XSS($values['glossary_title']))."',
							description	= '".Database::escape_string(Security::remove_XSS(stripslashes(api_html_entity_decode($values['glossary_comment'])),COURSEMANAGERLOWSECURITY))."'
					WHERE glossary_id = ".Database::escape_string($values['glossary_id']);
			$result = Database::query($sql, __FILE__, __LINE__);
			//update glossary into item_property
			api_item_property_update(api_get_course_info(), TOOL_GLOSSARY, Database::escape_string($values['glossary_id']), 'GlossaryUpdated', api_get_user_id());
			// display the feedback message
			Display::display_confirmation_message(get_lang('TermUpdated'));
		}
	}
	
	/**
	 * Get the maximum display order of the glossary item
	 *
	 * @author Christian Fasanando <christian.fasanando@dokeos.com>
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function get_max_glossary_item()
	{
		// Database table definition
		$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
	
		$get_max = "SELECT MAX(display_order) FROM $t_glossary";
		$res_max = Database::query($get_max, __FILE__, __LINE__);
		$dsp=0;
		$row = Database::fetch_array($res_max);
		return $row[0];
	}
	
	/**
	 * check if the glossary term exists or not
	 *
	 * @param unknown_type $term
	 * @param unknown_type $not_id
	 * @return unknown
	 *
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function glossary_exists($term,$not_id='')
	{
		// Database table definition
		$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
	
		$sql = "SELECT name FROM $t_glossary WHERE name = '".Database::escape_string($term)."'";
		if ($not_id<>'')
		{
			$sql .= " AND glossary_id <> '".Database::escape_string($not_id)."'";
		}
		$result = Database::query($sql,__FILE__,__LINE__);
		$count = Database::num_rows($result);
		if ($count > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	/**
	 * get all the information about one specific glossary term
	 *
	 * @param unknown_type $glossary_id
	 * @return unknown
	 *
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function get_glossary_information($glossary_id)
	{
		// Database table definition
		$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
		$t_item_propery = Database :: get_course_table(TABLE_ITEM_PROPERTY);
	
		$sql = "SELECT 	g.glossary_id 		AS glossary_id,
						g.name 				AS glossary_title,
						g.description 		AS glossary_comment,
						g.display_order		AS glossary_display_order
				   FROM $t_glossary g, $t_item_propery ip
				   WHERE g.glossary_id = ip.ref
				   AND tool = '".TOOL_GLOSSARY."'
				   AND g.glossary_id = '".Database::escape_string($glossary_id)."' ";
		$result = Database::query($sql, __FILE__, __LINE__);
		return Database::fetch_array($result);
	}
	
	/**
	 * Delete a glossary term (and re-order all the others)
	 *
	 * @param integer $glossary_id the id of the glossary
	 *
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function delete_glossary($glossary_id)
	{
		// Database table definition
		$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
	
		$sql = "DELETE FROM $t_glossary WHERE glossary_id='".Database::escape_string($glossary_id)."'";
		$result = Database::query($sql, __FILE__, __LINE__);
	
		//update item_property (delete)
		api_item_property_update(api_get_course_info(), TOOL_GLOSSARY, Database::escape_string($glossary_id), 'delete', api_get_user_id());
	
		// reorder the remaining terms
		GlossaryManager::reorder_glossary();
		$_SESSION['max_glossary_display'] = GlossaryManager::get_max_glossary_item();
		Display::display_confirmation_message(get_lang('TermDeleted'));
	}
	
	/**
	 * This is the main function that display the list or the table with all the glossary terms
	 *
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function display_glossary()
	{
		// action links
		echo '<div class="actions" style="margin-bottom:10px">';
		if (api_is_allowed_to_edit(null,true))
		{
			echo '<a href="index.php?'.api_get_cidreq().'&action=addglossary&msg=add">'.Display::return_icon('filenew.gif',get_lang('TermAddNew')).get_lang('TermAddNew').'</a>';
		}
	
		if ((isset($_SESSION['glossary_view']) && $_SESSION['glossary_view'] == 'table') or (!isset($_SESSION['glossary_view']))){
			echo '<a href="index.php?'.api_get_cidreq().'&action=changeview&view=list">'.Display::return_icon('view_list.gif',get_lang('ListView')).get_lang('ListView').'</a>';
		} else {
			echo '<a href="index.php?'.api_get_cidreq().'&action=changeview&view=table">'.Display::return_icon('view_table.gif',get_lang('TableView')).get_lang('TableView').'</a>';
		}
		echo '</div>';
		if (!$_SESSION['glossary_view'] OR $_SESSION['glossary_view'] == 'table')
		{
			$table = new SortableTable('glossary', array('GlossaryManager','get_number_glossary_terms'), array('GlossaryManager','get_glossary_data'),0);
			$table->set_header(0, get_lang('DisplayOrder'), true);
			$table->set_header(1, get_lang('TermName'), true);
			$table->set_header(2, get_lang('TermDefinition'), true);
			$table->set_header(3, get_lang('CreationDate'), false);
			$table->set_header(4, get_lang('UpdateDate'), false);
			if (api_is_allowed_to_edit(null,true)) {
				$table->set_header(5, get_lang('Actions'), false);
				$table->set_column_filter(5, array('GlossaryManager','actions_filter'));
			}
			$table->display();
		}
		if ($_SESSION['glossary_view'] == 'list')
		{
			GlossaryManager::display_glossary_list();
		}
	}
	
	/**
	 * display the glossary terms in a list
	 *
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function display_glossary_list()
	{
		$glossary_data = GlossaryManager::get_glossary_data(0,1000,0,ASC);
		foreach($glossary_data as $key=>$glossary_item)
		{
			echo '<div class="sectiontitle">'.$glossary_item[1].'</div>';
			echo '<div class="sectioncomment">'.$glossary_item[2].'</div>';
			if (api_is_allowed_to_edit(null,true)) {
				echo '<div>'.GlossaryManager::actions_filter($glossary_item[5], '',$glossary_item).'</div>';
			}
		}
	}
	
	/**
	 * Get the number of glossary terms
	 *
	 * @return unknown
	 *
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function get_number_glossary_terms()
	{
		// Database table definition
		$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
	
		$sql = "SELECT count(glossary_id) as total FROM $t_glossary";
		$res = Database::query($sql, __FILE__, __LINE__);
		$obj = Database::fetch_object($res);
		return $obj->total;
	}
	
	/**
	 * get all the data of the glossary
	 *
	 * @param unknown_type $from
	 * @param unknown_type $number_of_items
	 * @param unknown_type $column
	 * @param unknown_type $direction
	 * @return unknown
	 *
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function get_glossary_data($from, $number_of_items, $column, $direction)
	{
		global $_user;
		// Database table definition
		$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
		$t_item_propery = Database :: get_course_table(TABLE_ITEM_PROPERTY);
	
		if (api_is_allowed_to_edit(null,true)) {
			$col5 = ", glossary.glossary_id	as col5";
		} else {
			$col5 = " ";
		}
	
		//condition for the session
		$session_id = api_get_session_id();
		$condition_session = api_get_session_condition($session_id);
	
		$sql = "SELECT
					glossary.display_order 	as col0,
					glossary.name 			as col1,
					glossary.description 	as col2,
					ip.insert_date			as col3,
					ip.lastedit_date		as col4
					$col5,
					glossary.session_id as session_id
				FROM $t_glossary glossary, $t_item_propery ip
				WHERE glossary.glossary_id = ip.ref
				AND tool = '".TOOL_GLOSSARY."' $condition_session";
		$sql .= " ORDER BY col$column $direction ";
		$sql .= " LIMIT $from,$number_of_items";
	
		$res = Database::query($sql, __FILE__, __LINE__);
	
		$return = array();
		$array = array();
		while ($data = Database::fetch_array($res)) {
	
			$array[0] = $data[0];
	
			//validacion when belongs to a session
			$session_img = api_get_session_image($data['session_id'], $_user['status']);
			$array[1] = $data[1] . $session_img;
	
			if (!$_SESSION['glossary_view'] || $_SESSION['glossary_view'] == 'table') {
				$array[2] = str_replace(array('<p>','</p>'),array('','<br />'),$data[2]);
			} else {
				$array[2] = $data[2];
			}
	
			$array[3] = $data[3];
			$array[4] = $data[4];
	
			if (api_is_allowed_to_edit(null,true)) {
				$array[5] = $data[5];
			}
	
			$return[] = $array;
		}
	
		return $return;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $glossary_id
	 * @param unknown_type $url_params
	 * @param unknown_type $row
	 * @return unknown
	 *
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function actions_filter($glossary_id,$url_params,$row)
	{
		if (!$_SESSION['max_glossary_display'] OR $_SESSION['max_glossary_display'] == '') {
			$_SESSION['max_glossary_display'] = GlossaryManager::get_max_glossary_item();
		}
	
		if (empty($_GET['glossary_column'])) {
			if ($row[0] > 1) {
				
				$return .= '<a href="'.api_get_self().'?action=moveup&amp;glossary_id='.$row[5].'&'.api_get_cidreq().'">'.Display::return_icon('up.gif', get_lang('Up')).'</a>';
			}
			else
			{
				$return .= Display::return_icon('up_na.gif','&nbsp;');
	
			}
			if ($row[0] < $_SESSION['max_glossary_display'])
			{
				$return .= '<a href="'.api_get_self().'?action=movedown&amp;glossary_id='.$row[5].'&'.api_get_cidreq().'">'.Display::return_icon('down.gif',get_lang('Down')).'</a>';
			}
			else
			{
				$return .= Display::return_icon('down_na.gif','&nbsp;');
	
			}
		}
		$return .= '<a href="'.api_get_self().'?action=edit_glossary&amp;glossary_id='.$row[5].'&'.api_get_cidreq().'&msg=edit">'.Display::return_icon('edit.gif',get_lang('Edit')).'</a>';
	
		$glossary_data = GlossaryManager::get_glossary_information($row[5]);
		$glossary_term = $glossary_data['glossary_title'];
	
		$return .= '<a href="'.api_get_self().'?action=delete_glossary&amp;glossary_id='.$row[5].'&'.api_get_cidreq().'" onclick="return confirmation(\''.$glossary_term.'\');">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
		return $return;
	}
	
	/**
	 * a little bit of javascript to display a prettier warning when deleting a term
	 *
	 * @return unknown
	 *
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function javascript_glossary()
	{
		return "<script type=\"text/javascript\">
				function confirmation (name)
				{
					if (confirm(\" ". get_lang("TermConfirmDelete") ." \"+ name + \" ?\"))
						{return true;}
					else
						{return false;}
				}
				</script>";
	}
	
	/**
	 * Enter description here...
	 *
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function reorder_glossary()
	{
		// Database table definition
		$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
	
		$sql = "SELECT * FROM $t_glossary ORDER by display_order ASC";
		$res = Database::query($sql, __FILE__, __LINE__);
	
		$i = 1;
		while ($data = Database::fetch_array($res))
		{
			$sql_reorder = "UPDATE $t_glossary SET display_order = $i WHERE glossary_id = '".Database::escape_string($data['glossary_id'])."'";
			Database::query($sql_reorder, __FILE__, __LINE__);
			$i++;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $direction
	 * @param unknown_type $glossary_id
	 *
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 */
	function move_glossary($direction, $glossary_id)
	{
		// Database table definition
		$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
	
		// sort direction
		if ($direction == 'up')
		{
			$sortorder = 'DESC';
		}
		else
		{
			$sortorder = 'ASC';
		}
	
		$sql = "SELECT * FROM $t_glossary ORDER BY display_order $sortorder";
		$res = Database::query($sql, __FILE__, __LINE__);
		$found = false;	
		while ($row = Database::fetch_array($res))
		{
			if ($found == true and empty($next_id))
			{
				$next_id = $row['glossary_id'];
				$next_display_order = $row['display_order'];
			}
	
			if ($row['glossary_id'] == $glossary_id)
			{
				$current_id = $glossary_id;
				$current_display_order = $row['display_order'];
				$found = true;
			}
	
		}
	
		$sql1 = "UPDATE $t_glossary SET display_order = '".Database::escape_string($next_display_order)."' WHERE glossary_id = '".Database::escape_string($current_id)."'";
		$sql2 = "UPDATE $t_glossary SET display_order = '".Database::escape_string($current_display_order)."' WHERE glossary_id = '".Database::escape_string($next_id)."'";
		
		$res = Database::query($sql1, __FILE__, __LINE__);
		$res = Database::query($sql2, __FILE__, __LINE__);
	
		Display::display_confirmation_message(get_lang('TermMoved'));
	}
}
?>