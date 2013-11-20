<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

 // include base class
if ( ! class_exists('Taxonomy_base'))
{
	require_once(PATH_THIRD.'taxonomy/base.taxonomy.php');
}

// ------------------------------------------------------------------------

class Taxonomy_model extends Taxonomy_base 
{

	/**
	 * Tree Table for module
	 *
	 * @var        string
	 * @access     public
	 */
	public $tree_table;

	/**
	 * Tree Table ID for module
	 *
	 * @var        string
	 * @access     public
	 */
	public $tree_id;

	protected $node = array(
		'node_id' 		=> '',
		'entry_id' 		=> '',
		'template_path' => '',
		'type' 			=> array(),
		'custom_url' 	=> '',
		'url_title' 	=> '',
		'lft' 			=> '',
        'rgt' 			=> '',
        'parent' 		=> ''
	);

	/**
	 * Constructor
	 *
	 * @access     public
	 * @return     void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Declares the table which the class operates on.
	 * @param $tree_id The id of the tree table (exp_taxonomy_tree_x)
	 * @return true if table set, false if not set (table doesn't exist)
	 */
	function set_table($tree_id = '')
	{

		if($tree_id != NULL || $tree_id != '')
		{
			// set our table
			$this->tree_table 	= ee()->db->dbprefix.'taxonomy_tree_'.$tree_id;
			$this->tree_id 		= $tree_id;

			// verify the table exists
			if( !isset($this->cache['set'][$this->tree_id]) )
			{
				if (ee()->db->table_exists( $this->tree_table ) === FALSE)
				{
					return FALSE;
				}
			}
			else
			{
				$this->cache['set'][$this->tree_id] = 1;
				return TRUE;
			}
		}
		else
		{
			return FALSE;
		}

	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetches all the site trees
	 * @param $site_id of the trees to fetch
	 * @return void
	 */
	function get_trees()
	{
		$trees = ee()->db->get_where('taxonomy_trees', array('site_id' => $this->site_id) )->result_array();
		// reindex with node ids as keys
		$data = array();
		foreach($trees as $tree)
		{
			$data[ $tree['id'] ] = $tree;
			$data[ $tree['id'] ]['templates'] = explode('|', $tree['templates']);
			$data[ $tree['id'] ]['channels'] = explode('|', $tree['channels']);
			$data[ $tree['id'] ]['member_groups'] = explode('|', $tree['member_groups']);

		}

    	return $data;
	}


	// ----------------------------------------------------------------

	/**
	 * Get tree by id
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_tree( $flush = FALSE)
    {

    	if( isset($this->cache['trees'][$this->tree_id]) && $flush === FALSE)
    	{
    		return $this->cache['trees'][$this->tree_id];
    	}
    	else
    	{
    		$data = ee()->db->get_where('taxonomy_trees', array('id' => $this->tree_id), 1 )->row_array();

			$data['templates'] = ($data['templates']) ? explode('|', $data['templates']) : array();
			$data['channels'] = ($data['channels']) ? explode('|', $data['channels']) : array();
			$data['member_groups'] = ($data['member_groups']) ? explode('|', $data['member_groups']) : array();
			$data['fields'] = ($data['fields']) ? json_decode($data['fields'], TRUE) : array();
			$data['taxonomy'] = ($data['taxonomy']) ? json_decode($data['taxonomy'], TRUE) : '';

    		$this->cache['trees'][$this->tree_id] = $data;

    	}

    	return $this->cache['trees'][$this->tree_id];

    }

    // ----------------------------------------------------------------

	/**
	 * Get nodes return an array of the node_id => node data, 
	 * as well as entry_id => node_id
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_nodes()
    {

    	if( !isset($this->cache['trees'][$this->tree_id]['nodes']))
    	{
    		ee()->db->select('*');
			ee()->db->from( $this->tree_table );
			ee()->db->join('channel_titles', 'channel_titles.entry_id = '.$this->tree_table.'.entry_id', 'left');
			ee()->db->join('statuses', 'statuses.status = channel_titles.status', 'left');
    		$nodes = ee()->db->get()->result_array();
    		
    		// reindex with node ids as keys
    		$node_data = $entry_data = array();
    		foreach($nodes as $node)
    		{
    			// if the node is associated with an entry
    			// create another index for those
    			if($node['entry_id'])
    			{
    				$entry_data[ $node['entry_id'] ] = $node['node_id'];
    			}

    			if($node['type'] != '')
    			{
    				$node['type'] = explode('|', $node['type']);
    			}
    			else
    			{
    				$node['type'] = array();
    			}

    			if($node['field_data'] != '')
    			{
    				$node['field_data'] = json_decode($node['field_data'], TRUE);
    				foreach($node['field_data'] as $k => $v)
    				{
    					$node[$k] = $v;
    				}
    			}

    			$node_data[ $node['node_id'] ] = $node;
    			$node_data[ $node['node_id'] ]['url'] = $this->build_url($node);
    			
    		}

    		$this->cache['trees'][$this->tree_id]['nodes']['by_node_id'] = $node_data;
    		$this->cache['trees'][$this->tree_id]['nodes']['by_entry_id'] = $entry_data;
    	}

    	return $this->cache['trees'][$this->tree_id]['nodes'];

    }


    // returns the url from a node array
    function build_url($node)
    {
    	// make sure we have the minimum properties of a node
    	$node = array_merge($this->node, $node);

    	// default to nada
    	$url = '';

    	// if we have a url override just use that
    	if( $node['custom_url'] ) 
    	{	
    		// @todo - review this decision as people may want relatives
    		// without the site index prepended.
    		// does the custom url start with a '/', if so add site index
    		if(isset($node['custom_url'][0]) && $node['custom_url'][0] == '/')
    		{
    			$node['custom_url'] = ee()->functions->fetch_site_index().$node['custom_url'];
    		}

    		$url = $node['custom_url'];

    	}
    	// associated with an entry or template?
    	elseif( $node['entry_id'] || $node['template_path'] ) 
    	{

    		// does this have a pages/structure uri
    		$pages_uri = $this->entry_id_to_page_uri( $node['entry_id'] );

    		if($pages_uri)
    		{
    			$url = $pages_uri;
    		}
    		else
    		{

    			if($node['template_path'])
	    		{
	    			$templates = $this->get_templates();
	    			$url .= (isset($templates['by_id'][ $node['template_path'] ])) ? '/'.$templates['by_id'][ $node['template_path'] ] : '';
	    		}

	    		if($node['entry_id'])
				{
					$url .= '/'.$node['url_title'];
				}

				if($node['entry_id'] || $node['template_path'])
				{
					$url = ee()->functions->fetch_site_index().$url;
				}
    		}

    	}

    	if($url && $url != '/')
    	{
    		// remove double slashes
    		$url = preg_replace("#(^|[^:])//+#", "\\1/", $url);
			// remove trailing slash
			$url = rtrim($url,"/");
    	}

    	return $url;

    }

    function node_url($node_id)
    {
    	$nodes = $this->get_nodes();
    	
    	if(!isset($nodes['by_node_id'][$node_id]))
    	{
    		return FALSE;
    	}
    }

    // --------------------------------------------------------------------
	/*
	 * returns the pages module uri for a given entry
	 * @param entry_id
	 * @param site_id
	 * @return string
	 */
	function entry_id_to_page_uri($entry_id, $site_id = '')
	{
		
		$site_id = ($site_id != '') ? $site_id : $this->site_id;
		
		if($site_id != $this->site_id)
		{
			$this->load_pages($site_id);
		}
		
		$site_pages = ee()->config->item('site_pages');

		if ($site_pages !== FALSE && isset($site_pages[$site_id]['uris'][$entry_id]))
		{
			$site_url = $site_pages[$site_id]['url'];
			$node_url = $site_url.$site_pages[$site_id]['uris'][$entry_id];
		}
		else
		{
			// not sure what else to do really?
			$node_url = NULL;
		}
		
		return $node_url;
		
	}

	// --------------------------------------------------------------------
	
	// load pages from another site, work in progress.
	// @todo	
	function load_pages($site_id)
	{
		
		$site_pages = ee()->config->item('site_pages');
		
		if( !isset($site_pages[$site_id]) )
		{
			ee()->db->select('site_pages, site_id');
			ee()->db->where_in('site_id', $site_id);
			$query = ee()->db->get('sites');
	
			$new_pages = array();
	
			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					$site_pages = unserialize(base64_decode($row['site_pages']));
	
					if (is_array($site_pages))
					{
						$new_pages += $site_pages;
					}
				}
			}
	
			ee()->config->set_item('site_pages', $new_pages);
		}

	}


    // --------------------------------------------------------------------

	/**
	 * Get a flat array of nodes
	 */
	 function get_flat_tree( $root = 1 )
	 {
	 	
		$node = $this->get_node( $root );
		if($node == false)
			return false;

		$query = ee()->db->select('*')
			->from( $this->tree_table )
			->join('channel_titles', 'channel_titles.entry_id = '.$this->tree_table.'.entry_id', 'left')
			->where($this->tree_table.".lft BETWEEN ".$node['lft']." AND ".$node['rgt'])
			->group_by($this->tree_table.".node_id")
			->order_by($this->tree_table.".lft", "asc")
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
	 * Returns the node with $col value of $val.
	 * @param $tree_id The tree id of the requested node.
	 * @param $val The val of the requested node.
	 * @param $lft The col of the requested node (lft, rgt, id etc).
	 * @return An asociative array of node values
	 */
	function get_node( $val, $col = 'lft' )
	{
		$node_data = array(
			'node_id' => '',
			'lft' => 0,
			'rgt' => 0,
			'parent' => '',
			'moved' => '',
			'label' => '',
			'entry_id' => '',
			'template_path' => '',
			'custom_url' => '',
			'type' => array(),
			'field_data' => '',
			'depth' => ''
		);

		$query = ee()->db->get_where( 
			$this->tree_table, 
			array($col => $val), 
			1 
		);
		return $query->num_rows() ? $query->row_array() : $node_data;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Returns the node with $col value of $val.
	 * @param $tree_id The tree id of the requested node.
	 * @param $val The val of the requested node.
	 * @param $lft The col of the requested node (lft, rgt, id etc).
	 * @return An asociative array of node values
	 */
	function get_node_by_id( $node_id, $site_id = NULL )
	{
		$site_id = ($site_id) ? $site_id : $this->site_id;
		$nodes = $this->get_nodes();

		$node_data = array(
			'node_id' => '',
			'lft' => 0,
			'rgt' => 0,
			'parent' => '',
			'moved' => '',
			'label' => '',
			'entry_id' => '',
			'template_path' => '',
			'custom_url' => '',
			'type' => array(),
			'field_data' => ''
		);

		if( isset($nodes['by_node_id'][$node_id]) )
		{
			$node_data = $nodes['by_node_id'][$node_id];
		}

		return $node_data;

	}

	// --------------------------------------------------------------------
	
	/**
	 * Returns the sibling entry ids of a given node
	 * @return An array of nodes
	 */
	function get_siblings( $id, $type = 'entry_id')
	{
		$siblings = array();
		$this_node = $this->get_node($id, $type);
		if($this_node['parent'])
		{
			ee()->db->order_by("lft", "asc"); 
			$siblings = ee()->db->get_where( 
				$this->tree_table, 
				array('parent' => $this_node['parent'])
			)->result_array();
		}
		return $siblings;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns the entry_id of an entry by url_title
	 * @param url_title
	 * @return int entry_id,
	 * but if no rows returned, false
	 */
	function entry_id_from_url_title($url_title)
	{
        ee()->db->where('url_title', $url_title)->limit(1);
        $entry = ee()->db->get('channel_titles')->row_array();            
        if($entry)
        {
            return $entry['entry_id'];
        }
        return false;
	}

	// ------------------------------------------------------------------------

    /**
	 * Get templates
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_templates( $site_id = '' )
    {
    	
    	if ( ! isset($this->cache['templates']) )
		{
			ee()->load->model('template_model');
			$r = $groups = array();

			(int) $site_id = ( !$site_id ) ? $this->site_id : $site_id;

			$templates = ee()->template_model->get_templates( $site_id )->result_array();
			$template_groups = ee()->template_model->get_template_groups( $site_id )->result_array(); 

			foreach($template_groups as $template_group)
			{
				$groups[ $template_group['group_name'] ] = $template_group;
			}

			foreach ($templates as $template)
			{	
				
				$r['by_group'][ $template['group_name'] ][ $template['template_id'] ] =  $template['template_name'];

				if($template['template_name'] == 'index')
				{
					$template['template_name'] = '';
				}
				else
				{
					$template['template_name'] = '/'.$template['template_name'];
				}

				// strip the group name if it's the site default
				$template['group_name'] = ($groups[ $template['group_name'] ]['is_site_default'] == 'y') ? '' : $template['group_name'];

				$r['by_id'][$template['template_id']] = '/'.$template['group_name'].$template['template_name'];

				natcasesort($r['by_id']);

			}

			$this->cache['templates'] =  $r;

		}

		return $this->cache['templates'];
    }

     // ------------------------------------------------------------------------

    /**
	 * Get Channels
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_channels( $site_id = '' )
    {
    	
    	if ( ! isset($this->cache['channels'] ))
		{
			$r = array();

			(int) $site_id = ( !$site_id ) ? $this->site_id : $site_id;

			ee()->db->select('channel_title, channel_name, channel_id, cat_group, status_group, field_group');
			ee()->db->where('site_id', $site_id);
			ee()->db->order_by('channel_title');
			$channels = ee()->db->get('channels')->result_array(); 

			foreach ($channels as $channel)
			{
				$r[ $channel['channel_id'] ] = $channel['channel_title'];
			}

			$this->cache['channels'] = $r;

		}

		return $this->cache['channels'];
    }

    // --------------------------------------------------------------------

	/**
	 * Get a flat array of entries
	 * @param	array or int of channels/channel id/s
	 */
	 function get_entries($channels)
	 {

	 	ee()->load->model('channel_entries_model');

		$fields = array( 
			"entry_id", 
			"channel_id", 
			"title"
		);

		return ee()->channel_entries_model->get_entries( $channels, $fields )->result_array();
	 }

    // --------------------------------------------------------------------

	/**
	 * Get a tree's taxonomy
	 */
	 function get_tree_taxonomy($node = '')
	 {

		$right = array();
		$result = array();
		$current =& $result;
		$stack = array();
		$stack[0] =& $result;
		$lastlevel = 0;
		$level = 1;

		ee()->db->select('node_id as id, rgt, depth')->from( $this->tree_table );

		if($node != '')
		{
			ee()->db->where('lft BETWEEN '.$node['lft'].' AND '.$node['rgt']);
		}

		ee()->db->group_by($this->tree_table.".lft")
					 ->order_by($this->tree_table.".lft", "asc");

		$query = ee()->db->get();
		
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
				'level' => $level,
				'depth' => $row['depth']
			);
			// go one level deeper with the index
			
			$lastlevel = count($right);
			$right[] = $row['rgt'];
		}
		
		return $result;
	}


	// --------------------------------------------------------------------
	
	/**
	 * Returns an array of nodes from the node with $lft & $rgt to the root
	 * @param $left The lft of the requested node.
	 * @param $rgt The rgt of the requested node.
	 * @return array
	 */
	function get_all_parents( $lft, $rgt)
	{

		if ( ! isset($this->cache['parents'][$this->tree_id][$lft.'|'.$rgt] ))
		{

			ee()->db->select('node_id')
						 ->where('lft <', $lft)
						 ->where('rgt >', $rgt)
						 ->group_by("lft")
						 ->order_by("lft", "asc");

			$data = ee()->db->get( $this->tree_table )->result_array();

			$return = array();
			
			foreach($data as $parent)
			{
				$return[] = $parent['node_id'];
			}

			$this->cache['parents'][$this->tree_id][$lft.'|'.$rgt] = $return;

		}
		return $this->cache['parents'][$this->tree_id][$lft.'|'.$rgt];

	}

	// --------------------------------------------------------------------

	//////////////////////////////////////////
	//  Insert functions
	//////////////////////////////////////////	
	
	/**
	 * Creates the root node in the table.
	 * @param $tree_id The id of the tree
	 * @param $data The root node data
	 * @return true if success, but if rootnode exists, it returns false
	 */
	function insert_root($data)
	{
		$table = $this->tree_table;

		$this->_lock_tree_table();

		if($this->get_root() != false) 
		{
			$this->_unlock_tree_table();
			return false;
		}
		$data = $this->_sanitize_data( $data );
		$data['depth'] = 0;
		$data = array_merge( $data, array('lft' => 1, 'rgt' => 2) );
		unset($data['node_id']);

		if(!isset($data['moved']))
		{
			$data['moved'] = 0;
		}
		
		ee()->db->insert( $this->tree_table, $data );
		$this->_unlock_tree_table();
		return true;
	}

	// --------------------------------------------------------------------
	
	
	/**
	 * Inserts a node at the lft specified.
	 * Primarily for internal use.
	 * @since 0.1
	 * @param $lft The lft of the node to be inserted
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @param $lock If the method needs to aquire a lock, default true
	 * Use this option when calling from a method wich already have got a lock on the tables used
	 * by this method.
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function insert_node( $lft, $data, $lock = true )
	{
		$root = $this->get_root();

		if($lft > $root['rgt'] || $lft < 1)return FALSE;
		
		$data = $this->_sanitize_data($data);
		
		if ($lock)
			$this->_lock_tree_table();

		ee()->db->query('UPDATE '.$this->tree_table.
						' SET lft = lft + 2 '.
						' WHERE lft >= '.$lft);

		ee()->db->query('UPDATE '.$this->tree_table.
						' SET rgt = rgt + 2 '.
						' WHERE rgt >= '.$lft);
		
		$data = array_merge( $data, array('lft' => $lft, 'rgt' => $lft+1) );
		unset($data['node_id']);

		if(!isset($data['moved']))
		{
			$data['moved'] = 0;
		}

		ee()->db->insert( $this->tree_table, $data );
		
		if ($lock)
			$this->_unlock_tree_table();
		
		return array( $lft, $lft + 1, ee()->db->insert_id() );
	}

	// --------------------------------------------------------------------
	
	/**
	 * Inserts the node as the last child the node with the lft specified.
	 * @param $lft The lft of the node to be parent
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function append_node_last( $lft, $data )
	{
		$node = $this->get_node($lft);

		if(!$node)
			return false;

		return $this->insert_node( $node['rgt'], $data );
	}

	// --------------------------------------------------------------------
	
	/**
	 * Inserts the node as the last child the node with the node_id specified.
	 * @param $node_id The id of the node to be parent
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function append_node_last_by_id( $node_id, $data )
	{
		$node = $this->get_node_by_id($node_id);

		if(!$node)
			return false;

		return $this->insert_node( $node['rgt'], $data );
	}

	// --------------------------------------------------------------------

	// @todo
	// this is requiring a complete overhaul. 
	// The incoming data should be verified, eg a good nested set
	function reorder_nodes($taxonomy_data)
	{

		$root_node = $this->get_root();

		$this->_lock_tree_table();

		foreach ($taxonomy_data as $node) 
		{
			$resp['data']['taxonomy'][] = $node;

			$data = array(
				'node_id' => $node['item_id'],
				'lft' 	  => $node['left'],
				'rgt' 	  => $node['right'],
				'parent'  => $node['parent_id'],
				'depth'	  => $node['depth']-1
			);

			if($node['left'] == 1) // root node
			{
				unset($data['node_id']); 
				$data['parent'] = 0;
				ee()->db->where('lft', $data['lft']);
				ee()->db->update($this->tree_table, $data);
			}
			else
			{
				$data['parent'] = ($data['parent']) ? $data['parent'] : $root_node['node_id'];
				ee()->db->where('node_id', $data['node_id']);
				ee()->db->update($this->tree_table, $data);
			}

		}

		$this->_unlock_tree_table();
	}

	// --------------------------------------------------------------------
	
	//////////////////////////////////////////
	//  Lock functions
	//////////////////////////////////////////

	/**
	 * Locks tree table.
	 * This is a straight write lock - the database blocks until the previous lock is released
	 */
	function _lock_tree_table()
	{
		ee()->db->query("LOCK TABLE " .$this->tree_table . " WRITE");
	}

	/**
	 * Unlocks tree table.
	 * Releases previous lock
	 */
	function _unlock_tree_table()
	{
		$q = "UNLOCK TABLES";
		ee()->db->query($q);
	}


	// --------------------------------------------------------------------

	/**
	 * Update a tree's taxonomy and timestamp
	 */
	 function update_taxonomy( $tree_array = array(), $type = '', $data = '' )
	 {

		$tree['last_updated'] = time();

		if($tree_array)
			$tree['taxonomy'] = $tree_array;
		
		ee()->db->where('id', $this->tree_id);
		ee()->db->update( 'taxonomy_trees', $tree );

		// -------------------------------
		// 'taxonomy_updated' extension hook
		//  called when:
		//		update_node
		// 		reorder_nodes
		// 		delete_node
		//		post_save on fieldtype
		// -------------------------------
		if (ee()->extensions->active_hook('taxonomy_updated'))
		{
			ee()->extensions->call('taxonomy_updated', $this->tree_id, $type, $data);
		}
		
	 }

	// --------------------------------------------------------------------

	 /**
	 * Update Tree
	 *
	 * @access     public
	 * @param      array
	 * @param      int
	 * @return     int
	 */
    function update_tree( $data )
    {
    	if(isset($data['id']) && $data['id'] != '')
    	{
    		ee()->db->where('id', $data['id']);
			ee()->db->update('taxonomy_trees', $data);
			return $data['id'];
    	}
    	else
    	{
    		unset($data['id']); // sql strict mode complains
    		ee()->db->insert('taxonomy_trees', $data);
    		$id = ee()->db->insert_id();
    		$this->build_tree_table( $id );
    		return $id;
    	}

    }


	// --------------------------------------------------------------------

	/**
	 * Updates the node values.
	 * @param $tree_id The tree_id of the node to be manipulated
	 * @param $node_id The node_id of the node to be manipulated
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return true if success, false otherwise
	 */
	function update_node( $node_id, $data )
	{
		$data = $this->_sanitize_data($data);

		// no custom field data, set to null
		// fixes bug when only checkboxes are used and no
		// data is submitted.
		if(!isset($data['field_data']))
		{
			$data['field_data'] = '';
		}

		// Make the update
		ee()->db->where( 'node_id', $node_id );
		ee()->db->update( $this->tree_table, $data );
		return true;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get the root node
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_root()
    {	
		$query = ee()->db->get_where( $this->tree_table, array('lft' => 1), 1 );
		return ( $query->num_rows() ) ? $query->row_array() : FALSE;
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
		$r['templates'] 		= $this->get_templates( $site_id );
		$r['channels'] 			= $this->get_channels( $site_id );
		$r['all_member_groups'] = $this->get_all_member_groups( $site_id );

		$r['allowed_member_groups'] = array();

		// filter out those without access to Taxonomy
		foreach( $r['all_member_groups'] as $id => $group_label )
		{
			if( in_array($id, $this->groups_with_module_access()) )
			{
				$r['allowed_member_groups'][$id] = $group_label;
			}
		}
		
		return $r;
	}

	// ------------------------------------------------------------------------

    /**
	 * Get member groups
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */
    function get_all_member_groups( $site_id = '' )
    {
    	
    	
    	if ( ! isset($this->cache['member_groups'] ))
		{

			$r = array();

			(int) $site_id = ( !$site_id ) ? $this->site_id : $site_id;

			ee()->db->select( "group_id, group_title" );
			ee()->db->from( "member_groups" );
			ee()->db->where( "site_id", $site_id );
			$groups =  ee()->db->get()->result_array();

			foreach ($groups as $group)
			{
				$r[ $group['group_id'] ] = $group['group_title'];
			}

			$this->cache['member_groups'] = $r;

		}

		return $this->cache['member_groups'];
    }

    // ------------------------------------------------------------------------

    /**
	 * Get member groups with access to the Taxonomy Module
	 *
	 * @access     public
	 * @return     array
	 */
    function groups_with_module_access()
    {
    	if ( ! isset($this->cache['member_groups_with_access'] ))
		{
			$r = array();

			ee()->db->select( 'modules.module_id, module_member_groups.group_id' );
			ee()->db->where( 'LOWER('.ee()->db->dbprefix.'modules.module_name)', TAXONOMY_SHORT_NAME );
			ee()->db->join( 'module_member_groups', 'module_member_groups.module_id = modules.module_id' );
			$groups = ee()->db->get('modules')->result_array();

			foreach ($groups as $group)
			{
				$r[] =  $group['group_id'];
			}

			$this->cache['member_groups_with_access'] = $r;

		}

		return $this->cache['member_groups_with_access'];
    }

    // ------------------------------------------------------------------------

    /**
	 * Build our Tree Table
	 *
	 * @access     public
	 * @param      int
	 * @return     array
	 */

	function build_tree_table( $tree_id )
	{
	
		$fields = array(
			'node_id' => array(
				'type' => 'mediumint',
				'constraint'	 => '8',
				'unsigned'		 => TRUE,
				'auto_increment' => TRUE,
				'null' => FALSE
			),
																
			'lft' => array(
				'type' => 'mediumint',
				'constraint' => '8',
				'unsigned' =>	TRUE
			),
										
			'rgt' => array(
				'type' => 'mediumint',
				'constraint'	=> '8',
				'unsigned'	=>	TRUE
			),

			'depth' => array(
				'type' => 'mediumint',
				'constraint'	=> '8',
				'unsigned'	=>	TRUE
			),

			'parent' => array(
				'type' => 'mediumint',
				'constraint'	=> '8',
				'unsigned'	=>	TRUE
			),
										
			'moved'	=> array(
				'type' => 'tinyint',
				'constraint'	=> '1',
				'null' => FALSE
			),
																	
			'label'	=> array(
				'type' => 'varchar', 
				'constraint' => '255'
			),
										
			'entry_id' => array(
				'type' => 'int',
				'constraint' => '10', 
				'null' => TRUE),
										
			'template_path' => array(
				'type' => 'varchar', 
				'constraint' => '255'
			),							
										
			'custom_url' => array(
				'type' => 'varchar', 
				'constraint' => '250', 
				'null' => TRUE
			),

			'type' => array(
				'type' => 'varchar', 
				'constraint' => '250', 
				'null' => TRUE
			),
										
			'field_data' => array(
				'type' => 'text'
			)			
		);
			
		ee()->load->dbforge();
		ee()->dbforge->add_field( $fields );
		ee()->dbforge->add_key( 'node_id', TRUE );
		ee()->dbforge->create_table( 'taxonomy_tree_'.$tree_id );
		
		unset($fields);
				
	}

	// --------------------------------------------------------------------
	
	
	//////////////////////////////////////////////
	//  Delete functions
	//////////////////////////////////////////////
	
	/**
	 * Deletes the node with the lft specified and promotes all children.
	 * @param $lft The lft of the node to be deleted
	 * @return True if something was deleted, false if not
	 */
	function delete_node( $lft )
	{
		$node = $this->get_node($lft);
		if(!$node || $node['lft'] <= 1)
			return FALSE;
		// Lock table
		$this->_lock_tree_table();
		ee()->db->where( 'node_id', $node['node_id'] );
		ee()->db->delete(  $this->tree_table);
		$this->remove_gaps();
		$this->_unlock_tree_table();
		return TRUE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Deletes the node with the lft specified and all it's children.
	 * @param $lft The lft of the node to be deleted
	 * @return True if something was deleted, false if not
	 */
	function delete_branch( $lft )
	{
		$node = $this->get_node($lft);
		if(!$node || $node['lft'] == 1)
			return FALSE;
		// lock table
		$this->_lock_tree_table();
		ee()->db->where('lft BETWEEN '.$node['lft'].' AND '.$node['rgt']);
		ee()->db->delete( $this->tree_table );
		$this->remove_gaps();
		$this->_unlock_tree_table();
		return TRUE;
	}

	// --------------------------------------------------------------------
	
	
	//////////////////////////////////////////////
	//  Gap functions
	//////////////////////////////////////////////
	
	/**
	 * Creates an empty space inside the tree beginning at $pos and with size $size.
	 * Primary for internal use.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @param $pos The starting position of the empty space.
	 * @param $size The size of the gap
	 * @return True if success, false if not or if space is outside root
	 */
	function create_space( $pos, $size )
	{
		$root = $this->get_root();

		if($pos > $root['rgt'] || $pos < $root['lft'])
		{
			return FALSE;
		}

		ee()->db->query('UPDATE '.$this->tree_table.
			' SET lft = lft + '.$size.
			' WHERE lft >='.$pos);

		ee()->db->query('UPDATE '.$this->tree_table.
			' SET rgt = rgt'.' + '.$size.
			' WHERE rgt >='.$pos);

		return TRUE;
	}
	
	/**
	 * Returns the first gap in table.
	 * Primary for internal use.
	 * @return The starting pos of the gap and size
	 */
	function get_first_gap()
	{
		$ret = $this->find_gaps();
		return $ret === false ? false : $ret[0];
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Removes the first gap in table.
	 * Primary for internal use.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @return True if gap removed, false if none are found
	 */
	function remove_first_gap()
	{
		$ret = $this->get_first_gap();
		if($ret !== false)
		{
			ee()->db->query('UPDATE '.$this->tree_table.
				' SET lft = lft - '.$ret['size'].
				' WHERE lft > '. $ret['start']);

			ee()->db->query('UPDATE '.$this->tree_table.
				' SET rgt = rgt - '.$ret['size'].
				' WHERE rgt > '. $ret['start']);
			return true;
		}
		return false;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Removes all gaps in the table.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @return True if gaps are found, false if none are found
	 */
	function remove_gaps()
	{
		$ret = false;
		while($this->remove_first_gap() !== false)
		{
			$ret = true;
		}
		return $ret;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Finds all the gaps inside the tree.
	 * Primary for internal use.
	 * @return Returns an array with the start and size of all gaps,
	 * if there are no gaps, false is returned
	 */
	function find_gaps()
	{
		// Get all lfts and rgts and sort them in a list
		ee()->db->select('lft, rgt');
		ee()->db->order_by('lft','asc');
		$table = ee()->db->get( $this->tree_table );

		$nums = array();

		foreach($table->result() as $row)
		{
			$nums[] = $row->{'lft'};
			$nums[] = $row->{'rgt'};
		}
		
		sort($nums);
		
		// Init vars for looping
		$old = array();
		$current = 1;
		$foundgap = 0;
		$gaps = array();
		$current = 1;
		$i = 0;
		$max = max($nums);
		while($max >= $current)
		{
			$val = $nums[$i];
			if($val == $current)
			{
				$old[] = $val;
				$foundgap = 0;
				$i++;
			}
			else
			{
				// have gap or duplicate
				if($val > $current)
				{
					if(!$foundgap)$gaps[] = array('start'=>$current,'size'=>1);
					else
					{
						$gaps[count($gaps) - 1]['size']++;
					}
					$foundgap = 1;
				}
			}
			$current++;
		}
		return count($gaps) > 0 ? $gaps : false;
	}

	// --------------------------------------------------------------------

	//////////////////////////////////////////////
	//  Helper functions
	//////////////////////////////////////////////
	
	/**
	 * Sanitizes the data given.
	 * Removes the left_col and right_col from the data, if they exists in $data.
	 * @param $data The data to be sanitized
	 * @return The sanitized data
	 */
	private function _sanitize_data($data){
		// Remove fields which potentially can damage the tree structure
		if(is_array($data))
		{
			unset($data['lft']);
			unset($data['rgt']);
		}
		elseif(is_object($data))
		{
			unset($data->lft);
			unset($data->rgt);
		}

		if(isset($data['type']))
		{
			$data['type'] = implode('|', $data['type']);
		}

		// ensure entry_id is set correctly for sql strict_mode
		if(isset($data['entry_id']) && $data['entry_id'] != '')
		{
			$data['entry_id'] = (int) $data['entry_id'];
		}
		else
		{
			$data['entry_id'] = NULL;
		}

		unset($data['tree_id']);
		unset($data['is_root']);
		unset($data['parent_lft']);
		return $data;
	}

	/*
     * This function finds a node inside a subset, uses self-recursion.
     */
    function find_node($tree, $key='', $val='')
    {
        if(isset($tree[$key]) && $tree[$key] == $val)
        {
        	if(isset($tree['children']))
        	{
        		unset($tree['children']);
        	}
        	$this->cache['temp_node'] = $tree;
        }
        else
        {
            if(isset($tree['children']))
            {
                foreach($tree['children'] as $child)
                {
                    $this->find_node($child, $key, $val);
                } 
            }
        }
  
    }


	/*
     * -----------------------------------------------------------------------------
     * This will flatten the tree so a specific level.
     */
    function flatten_tree(&$tree, $start_level, &$items)
    {
        foreach($tree as $item)
        {
            if($item['level'] === (integer) $start_level)
            {
                $items[] = $item;
            }

            if(isset($item['children']))
            {
                $this->flatten_tree($item['children'], $start_level, $items);
            }
        }
    }

     /*
     * -----------------------------------------------------------------------------
     * This function finds an entry inside a subset, uses self-recursion.
     */
    function find_in_subset(&$item, $id, $depth, $type = 'entry_id')
    {
        if($item['level'] > $depth)
            return FALSE;

        if(isset($item[$type]) && $item[$type] == $id)
        {
            return TRUE;
        }
        else
        {
            if(isset($item['children']))
            {
                $found = FALSE;

                foreach($item['children'] as $child)
                {
                    if($this->find_in_subset($child, $id, $depth, $type))
                    {
                        $found = TRUE;
                    }
                }

                return $found;
                
            }
            else
            {
                return FALSE;
            }
        }
    }
    

	

}