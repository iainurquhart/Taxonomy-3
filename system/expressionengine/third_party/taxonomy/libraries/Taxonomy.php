<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Taxonomy Library Library
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Taxonomy Module
 * @author		Iain Urquhart
 * @link		http://iain.co.nz
 */

 // include base class
if ( ! class_exists('Taxonomy_base'))
{
	require_once(PATH_THIRD.'taxonomy/base.taxonomy.php');
}

class Taxonomy extends Taxonomy_base {

	function __construct() 
	{
		parent::__construct();
	}

	// ----------------------------------------------------------------

	/**
	 * Get trees
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_trees( $site_id = '' )
    {
    	return $this->EE->taxonomy_model->get_trees( $site_id );
    }

	// ----------------------------------------------------------------

	/**
	 * Get tree by id
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_tree( $tree_id, $flush = FALSE )
    {
    	return $this->EE->taxonomy_model->get_tree( $tree_id, $flush);
    }

    // ----------------------------------------------------------------

	/**
	 * Get templates
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_templates( $site_id )
    {
    	return $this->EE->taxonomy_model->get_templates( $site_id );
    }

    // ----------------------------------------------------------------

	/**
	 * Get nodes
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_nodes( $tree_id )
    {
    	$nodes = array();
    	$nodes_array = $this->EE->taxonomy_model->get_nodes( $tree_id );

    	foreach($nodes_array as $node)
    	{
    		$nodes[$node['node_id']] = $node;
    	}

    	unset($nodes_array);

    	return $nodes;
    }

    // ----------------------------------------------------------------

	/**
	 * Get node
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_node( $tree_id, $lft )
    {
    	return $this->EE->taxonomy_model->get_node( $tree_id, $lft );
    }

    // ------------------------------------------------------------------------

    /**
	 * Get tree options
	 * Build an array of available templates, channels, member groups
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_tree_options( $site_id = '' )
    {

  		(int) $site_id = ($site_id) ? $this->site_id : $site_id;

    	$r = array();

    	// fetch our arrays
		$r['templates'] 		= $this->EE->taxonomy_model->get_templates( $site_id );
		$r['channels'] 			= $this->EE->taxonomy_model->get_channels( $site_id );
		$r['all_member_groups'] = $this->EE->taxonomy_model->get_all_member_groups( $site_id );

		// get those with access to the Taxonomy module
		$groups_with_module_access =  $this->EE->taxonomy_model->groups_with_module_access();

		$r['allowed_member_groups'] = array();

		// filter out those without access to Taxonomy
		foreach( $r['all_member_groups'] as $id => $group_label )
		{
			if( in_array($id, $groups_with_module_access) )
			{
				$r['allowed_member_groups'][$id] = $group_label;
			}
		}
		
		return $r;
	}

	// ----------------------------------------------------------------

	/**
	 * Update a tree
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function update_tree( $data )
    {
    	return $this->EE->taxonomy_model->update_tree( $data );
    }

    // --------------------------------------------------------------------
	
	
	/**
	 * Inserts the node as the last child the node with the lft specified.
	 * @param $lft The lft of the node to be parent
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function append_node_last( $tree_id, $lft, $data )
	{
		$node = $this->EE->taxonomy_model->get_node($tree_id, $lft);

		if(!$node)
			return false;

		return $this->EE->taxonomy_model->insert_node( $tree_id, $node['rgt'], $data );
	}


	 // --------------------------------------------------------------------
	
	
	/**
	 * Inserts the node as the last child the node with the lft specified.
	 * @param $lft The lft of the node to be parent
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function update_node( $tree_id, $data )
	{
		$node_id = (isset($data['node_id'])) ? $data['node_id'] : '';

		if(!$node_id)
			return false;

		return $this->EE->taxonomy_model->update_node( $tree_id, $node_id, $data );
	}


    /*
	 * Checks a tree table exists, returns true if the table is found,
	 * Prevents mysql errors if exp_taxonomy_tree_x table isn't found.
	 * and adds to session array so subsequent requests don't need to hit the db
	 * allso sets the table (set_table), required for the class to run
	 * @param $tree_id
	 * @param $cp_redirect - set to true if we're to bounce the user in the cp
	 * @return true/false
	 */ 
	function validate_tree_table( $tree_id = '', $cp_redirect = FALSE )
	{
		
		if($tree_id == '') 
			return false; 

		if( ! isset( $this->EE->session->cache['taxonomy']['validation'][$tree_id]['validated']) )
		{
			if ( ! $this->EE->db->table_exists( 'taxonomy_tree_' . $tree_id) )
			{
				
				if($cp_redirect)
				{
					$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('tx_invalid_tree'));
					$this->EE->functions->redirect( $this->base_url );
				}
				$this->EE->session->set_cache('taxonomy', 'validation', array($tree_id => array('validated' => FALSE)));
				return FALSE;
			}

		}

		$this->EE->session->set_cache('taxonomy', 'validation', array($tree_id => array('validated' => TRUE)));

		return TRUE;

	}

	// --------------------------------------------------------------------
	
	
	/**
	 * Returns the node with col value of $id.
	 * @param $tree_id The tree_id of the requested node.
	 * @param $lft The id of the requested node.
	 * @param $col The col of the requested node (lft, rgt, id etc).
	 * @return An asociative array with the table row,
	 * but if no rows returned, false
	 */
	function spawn_node( $tree_id, $id, $col = 'lft' )
	{
		$node = $this->EE->taxonomy_model->get_node($tree_id, $id, $col);
		$node['type'] = explode('|', $node['type']);
		$node['field_data'] = ($node['field_data']) ? json_decode($node['field_data']) : array();
		return $node;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get the root node
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_root( $tree_id )
    {
    	return $this->EE->taxonomy_model->get_root($tree_id);
    }

	// --------------------------------------------------------------------

	/**
	 * Get a flat array of nodes
	 */
	 function get_flat_tree($tree_id, $root = 1)
	 {
	 	
		$node = $this->EE->taxonomy_model->get_node($tree_id, $root);
		if($node == false)
			return false;

		$tree_table = 'taxonomy_tree_'.$tree_id;

		$query = $this->EE->db->select('*')
			->from( $tree_table )
			->join('channel_titles', 'channel_titles.entry_id = '.$tree_table.'.entry_id', 'left')
			->where($tree_table.".lft BETWEEN ".$node['lft']." AND ".$node['rgt'])
			->group_by($tree_table.".node_id")
			->order_by($tree_table.".lft", "asc")
			->get();
		
		$right = array();
		$result = array();
		$current =& $result;
		$stack = array();
		$stack[0] =& $result;
		$level = 0;
		$i = 0;

		foreach($query->result_array() as $row)
		{

			// go more shallow, if needed
			if(count($right))
			{
				while($right[count($right)-1] < $row['rgt'])
				{
					array_pop($right);
				}
			}
			// Go one level deeper?
			if(count($right) > $level)
			{
				end($current);
			}
			// the stack contains all parents, current and maybe next level
			// $current =& $stack[count($right)];
			// add the data
			$current[] = $row;
			// go one level deeper with the index
			$level = count($right);
			$right[] = $row['rgt'];

			$current[$i]['level'] = $level;
			$current[$i]['childs'] = round(($row['rgt'] - $row['lft']) / 2, 0);
			$i++;
		}
		
		return $result;
		
	}


	// --------------------------------------------------------------------

	/**
	 * Get a flat array of nodes
	 */
	 function get_entries($channels)
	 {

	 	$this->EE->load->model('channel_entries_model');

		$fields = array( 
			"entry_id", 
			"channel_id", 
			"title"
		);

		return $this->EE->channel_entries_model->get_entries( $channels, $fields )->result_array();
	 }

	 // --------------------------------------------------------------------

	/**
	 * Get an array of channel names
	 */
	 function get_channels($site_id = '')
	 {
		return $this->EE->taxonomy_model->get_channels();
	 }

	// --------------------------------------------------------------------

	/**
	 * Insert a tree's root node
	 */
	 function insert_root( $tree_id, $data)
	 {
		$this->EE->taxonomy_model->insert_root( $tree_id, $data );
	 }

	 // --------------------------------------------------------------------

	/**
	 * Get a tree's taxonomy
	 */
	 function get_tree_taxonomy( $tree_id)
	 {

		$right = array();
		$result = array();
		$current =& $result;
		$stack = array();
		$stack[0] =& $result;
		$lastlevel = 0;
		$level = 1;

		$tree_table = 'taxonomy_tree_'.$tree_id;

		$query = $this->EE->db->select('node_id as id, rgt')
			->from( $tree_table )
			->group_by($tree_table.".lft")
			->order_by($tree_table.".lft", "asc")
			->get();
		
		foreach($query->result_array() as $row)
		{
		
			$level = count($right);
			$row['level'] = $level;
		
			// go more shallow, if needed
			if( count($right) > 1 )
			{
				while($right[count($right)-1] < $row['rgt'])
				{
					$level = $level-1;
					array_pop( $right );
					$row['level'] = $level;
				}
			}

			// Go one level deeper?
			if( count($right) > $lastlevel )
			{
				end($current);
				$current[key($current)]['children'] = array();
				$stack[count($right)] =& $current[key($current)]['children'];
				$row['level'] = $level;
			}
	
			// the stack contains all parents, current and maybe next level
			$current =& $stack[count($right)];
			// add the data
			$current[] = array(
				'id' => $row['id'],
				'level' => $level
			);
			// go one level deeper with the index
			
			$lastlevel = count($right);
			$right[] = $row['rgt'];
		}
		
		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Update a tree's taxonomy and timestamp
	 */
	 function update_taxonomy( $tree_id, $data)
	 {
		$this->EE->taxonomy_model->update_taxonomy( $tree_id, $data );
	 }











	// loads everything we need about a tree and it's nodes into the cache array
	function initialize_tree($tree_id = '')
	{

		if(!isset($this->cache['trees'][$tree_id]))
		{
			$this->EE->load->model('taxonomy_model');
			$this->EE->taxonomy_model->get_tree($tree_id);
			// $this->EE->taxonomy_model->get_nodes($tree_id);

		}

	}


	


}