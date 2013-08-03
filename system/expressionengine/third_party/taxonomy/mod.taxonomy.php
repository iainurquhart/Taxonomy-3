<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// ------------------------------------------------------------------------

/**
 * Taxonomy Module Front End File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Iain Urquhart
 * @link		http://iain.co.nz
 * @copyright 	Copyright (c) 2012 Iain Urquhart
 * @license   	Commercial, All Rights Reserved: http://devot-ee.com/add-ons/license/taxonomy/
 */

 // include base class
if ( ! class_exists('Taxonomy_base'))
{
	require_once(PATH_THIRD.'taxonomy/base.taxonomy.php');
}


// ------------------------------------------------------------------------

class Taxonomy extends Taxonomy_base {
	

	// --------------------------------------------------------------------
	//  P R O P E R T I E S
	// --------------------------------------------------------------------

	public $return_data;

	public $node_vars = array(
		'node_id' => '',
		'node_title' => 'asdsad', 
		'node_url' => '',
		'node_relative_url' => '',
		'node_active' => '',
		'node_active_parent' => '',
		'node_lft' => '',
		'node_rgt' => '',
		'node_entry_id' =>  '',
		'node_custom_url' => '',
		'node_field_data' =>  '',
		'node_entry_title' => '',
		'node_entry_url_title' => '',
		'node_entry_status' =>  '',
		'node_entry_entry_date' => '',
		'node_entry_expiration_date' => '',
		'node_entry_template_name' => '',
		'node_entry_template_group_name' => '',
		'node_has_children' => '',
		'node_next_child' => '',
		'node_level' => '',
		'node_level_count' => '',
		'node_level_total_count' => '',
		'node_count' => '',
		'node_previous_level' => '',
		'node_previous_level_diff' => '',
		'node_indent' => '',
		'ul_open' => '',
		'ul_close' => ''
	);

	private $vars = array();

	private $default_nav_tagdata = "\n<li{if node_active} class='active'{/if}><a href='{node_url}'>{node_title}</a>{children}</li>\n";

	private $default_breadcrumbs_tagdata = " {if here}{node_title}{if:else}<a href='{node_url}'>{node_title}</a> {node_delimiter} {/if}";
	
	/**
	 * Constructor
	 *
	 * @access     public
	 * @return     void
	 */
	public function __construct()
	{
		parent::__construct();
		ee()->load->model('taxonomy_model', 'taxonomy');
	}

	// --------------------------------------------------------------------
	//  P U B L I C   M E T H O D S
	// --------------------------------------------------------------------

	public function get_node()
	{
		return $this->_get_node(FALSE);
	}

	public function set_node()
	{
		return $this->_get_node();
	}

	/**
     * The the url for a node from tree_id + (entry_id or node_id)
	 */	
	public function node_url()
	{
		
		$tree_id 		= $this->_get_this('tree_id');
		$entry_id 		= $this->_get_this('entry_id');
		$node_id 		= $this->_get_this('node_id');
		$use_relative 	= $this->get_param('use_relative', FALSE);

		if(!$tree_id || (!$node_id && !$entry_id))
			return '';

		if( ee()->taxonomy->set_table( $tree_id ) === FALSE )
		{
			ee()->TMPL->log_item("TAXONOMY:NAV: Returning NULL; Tree requested does not exist.");
			return '';
		} 

		ee()->taxonomy->get_nodes();
		$node_cache = (isset($this->cache['trees'][$tree_id]['nodes'])) ? $this->cache['trees'][$tree_id]['nodes'] : '';

		// via entry_id
		if($entry_id && isset($node_cache['by_entry_id'][$entry_id]))
		{
			$node_id = $node_cache['by_entry_id'][$entry_id];
		}

		if($node_id && isset($node_cache['by_node_id'][$node_id]['url']))
		{
			
			if($use_relative) // bleh
			{
				return str_replace($this->site_url, '', $node_cache['by_node_id'][$node_id]['url']);
			}
			else
			{
				return $node_cache['by_node_id'][$node_id]['url'];
			}
			
		}

	}


	// ----------------------------------------------------------------
	
	
	/**
	 * Generates a breadcrumb trail from the current node with a specified entry_id/node_id
	 */
	function breadcrumbs()
	{
		
		$tree_id 		= $this->_get_this('tree_id');
		$entry_id 		= $this->_get_this('entry_id');
		$node_id 		= $this->_get_this('node_id');

		if(!$tree_id)
			return NULL;

		if( ee()->taxonomy->set_table( $tree_id ) === FALSE )
		{
			ee()->TMPL->log_item("TAXONOMY:NAV: Returning NULL; Tree requested does not exist.");
			return '';
		} 

		if(ee()->TMPL->tagdata == '')
		{
			ee()->TMPL->tagdata = $this->default_breadcrumbs_tagdata;
		}

		$display_root 	= $this->get_param('display_root', 'yes');
		$include_here 	= $this->get_param('include_here');
		$reverse 		= $this->get_param('reverse', 'no');
		$title_field 	= $this->get_param('title_field', 'node_title');
		$wrap_ul 		= $this->get_param('wrap_ul', 'no');
		$delimiter 		= $this->get_param('delimiter', '&rarr;');

		$this->return_data = $here = '';
		$depth = 0;	

		// load what we need
		$tree = ee()->taxonomy->get_tree();
		ee()->taxonomy->get_nodes(); // loads the session array with node data

		$node_cache = (isset($this->cache['trees'][$tree_id]['nodes'])) ? $this->cache['trees'][$tree_id]['nodes'] : array();

		// extract node_id from entry_id
		if($entry_id)
		{
			if(isset($node_cache['by_entry_id'][$entry_id]))
			{
				$node_id = $node_cache['by_entry_id'][$entry_id];
			}
		}
		// no entry_id or node_id, try and extract
		if(!$entry_id && !$node_id)
		{
			if(isset($node_cache['by_node_id']) && is_array($node_cache['by_node_id']))
			{
				foreach($node_cache['by_node_id'] as $key => $cached_node)
				{
					if($cached_node['url'] == current_url())
					{
						$this_node = $node_cache['by_node_id'][$key];
						break;
					}

				}
			}
		}
		else
		{
			$this_node = (isset($node_cache['by_node_id'][$node_id])) ? $node_cache['by_node_id'][$node_id] : $this->node_vars;
		}

		// don't have a node, bail out
		if(!isset($this_node) || $this_node['lft'] == '')
			return ee()->TMPL->no_results();

		$parents = ee()->taxonomy->get_all_parents( $this_node['lft'], $this_node['rgt'] );
		
		// set our current node vars
		$this_node_vars = array(
			'node_id' => $this_node['node_id'],
			'node_title' => $this_node['label'],
			'node_url' => $this_node['url'],
			'node_relative_url' => '', // @todo
			'node_lft' => $this_node['lft'],
			'node_rgt' => $this_node['rgt'],
			'node_entry_id' => $this_node['entry_id'],
			'node_custom_url' => $this_node['custom_url'],
			'node_field_data' => $this_node['field_data'],
			'node_entry_title' => $this_node['title'],
			'node_entry_url_title' => $this_node['url_title'],
			'node_entry_status' => $this_node['url'],
			'node_entry_entry_date' => $this_node['status'],
			'node_entry_expiration_date' => $this_node['expiration_date'],
			'node_entry_template_name' => '',
			'node_entry_template_group_name' => '',
			'node_next_child' => $this_node['lft']+1,
			'here' => 1,
			'not_here' => '',
			'node_count' => count($parents)+1,
			'node_total_count' => count($parents)+1,
			'node_level' => count($parents),
			'node_delimiter' => $delimiter
		);

		if(!empty($tree['fields']) && is_array($tree['fields']))
		{
			foreach($tree['fields'] as $field)
			{
				$field_data = (isset($this_node_vars['node_field_data'][$field['name']])) ? $this_node_vars['node_field_data'][$field['name']] : '';
				$this_node_vars[ $field['name'] ] = $field_data;
			}
		}

		$vars = array();

		if(count($parents))
		{	
			$i = 0;
			foreach($parents as $key => $p_id)
			{
				
				if((isset($node_cache['by_node_id'][$p_id])))
				{
					$att = $node_cache['by_node_id'][$p_id];

					$vars[$i] = array(
						'node_id' => $att['node_id'],
						'node_title' => $att['label'],
						'node_url' => $att['url'],
						'node_relative_url' => '', // @todo
						'node_lft' => $att['lft'],
						'node_rgt' => $att['rgt'],
						'node_entry_id' => $att['entry_id'],
						'node_custom_url' => $att['custom_url'],
						'node_field_data' => $att['field_data'],
						'node_entry_title' => $att['title'],
						'node_entry_url_title' => $att['url_title'],
						'node_entry_status' => $att['url'],
						'node_entry_entry_date' => $att['status'],
						'node_entry_expiration_date' => $att['expiration_date'],
						'node_entry_template_name' => '',
						'node_entry_template_group_name' => '',
						'node_next_child' => $att['lft']+1,
						'here' => '',
						'not_here' => 1,
						'node_count' => $key+1,
						'node_total_count' => count($parents)+1,
						'node_level' => $key,
						'node_delimiter' => $delimiter
					);
					
					if(!empty($tree['fields']) && is_array($tree['fields']))
					{
						foreach($tree['fields'] as $field)
						{
							$field_data = (isset($att['field_data'][$field['name']])) ? $att['field_data'][$field['name']] : '';
							$vars[$i][ $field['name'] ] = $field_data;
						}
					}
					$i++;
				}
			}
			$vars[] = $this_node_vars; // append our current
		}
		else
		{
			$vars[] = $this_node_vars; // no parents, just our current node then
		}

		// print_r($vars);

		if($display_root == 'no')
		{
			$vars = array_slice($vars, 1);
			foreach($vars as $key => &$var)
			{
				$var['node_count'] = $key+1;
				$var['node_total_count'] = count($vars);
			}
		}

		if($include_here == 'no')
		{
			$vars = array_slice($vars, 0, -1);
			foreach($vars as $key => &$var)
			{
				$var['node_count'] = $key+1;
				$var['node_total_count'] = count($vars);
			}
		}

		if($reverse == 'yes')
		{
			 $vars = array_reverse($vars);
			 foreach($vars as $key => &$var)
			 {
			 	$var['node_count'] = $key+1;
			 }
		}

		if(!count($vars)) return '';



		$r = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);

		if($wrap_ul == 'yes')
		{
			$r = "<ul>".$r."</ul>";
		}

		return $r;

		
	}
	

	// --------------------------------------------------------------------


	/**
	 * Nav
	 *
	 * Outputs navigation
	 *
	 * @return	string
	 */
	function nav()
	{
		$tree_id 		= $this->_get_this('tree_id');

		// no tree, no partay.
		if(!$tree_id)
			return '';

		$entry_id 		= $this->_get_this('entry_id');
		$node_id 		= $this->_get_this('node_id');
		$use_relative 	= $this->get_param('use_relative', FALSE);
		$url_base 		= ($use_relative === FALSE) ? $this->site_url : '';

		ee()->load->helper('url');

		if(ee()->TMPL->tagdata == '')
		{
			ee()->TMPL->tagdata = $this->default_nav_tagdata;
		}

		if( ee()->taxonomy->set_table( $tree_id ) === FALSE )
		{
			ee()->TMPL->log_item("TAXONOMY:NAV: Returning NULL; Tree requested does not exist.");
			return '';
		} 

		if (! isset(ee()->session->cache['taxonomy_node_count']))
		{
			ee()->session->cache['taxonomy_node_count'] = 1;
		}

		// tag parameters
		// --------------------------------------------------
		$params = array(
			'entry_id' => $entry_id,
			'node_id' => $node_id,
			'depth' => $this->get_param('depth', 100),
			'display_root' => $this->get_param('display_root', 'yes'),
			'root_lft' => $this->get_param('root_node_lft', 1),
			'root_node_id' => $this->get_param('root_node_id'),
			'root_node_entry_id' => $this->get_param('root_node_entry_id'),
			'ul_css_id' => $this->get_param('ul_css_id'),
			'ul_css_class' => $this->get_param('ul_css_class'),
			'hide_dt_group' => $this->get_param('hide_dt_group'),
			'root_node_url_title' => $this->get_param('root_node_url_title'),
			'auto_expand' => $this->get_param('auto_expand', 'no'),
			'style' => $this->get_param('style', 'nested'),
			'path' => '',
			'entry_status' => ($this->get_param('entry_status') ) ? explode('|', $this->get_param('entry_status')) : array('open'),
			'active_branch_start_level' => $this->get_param('active_branch_start_level', 0),
			'site_id' => $this->get_param('site_id', ee()->config->item('site_id')),
			'require_valid_entry_id' => $this->get_param('require_valid_entry_id', FALSE),
			'html_before' => $this->get_param('html_before', ''),
			'html_after' => $this->get_param('html_after', ''),
			'wrapper_ul' => $this->get_param('wrapper_ul', 'yes'),
			'node_id' => $this->get_param('node_id', ''),
			'exclude_node_id' => ( $this->get_param('exclude_node_id') ) ? explode('|', $this->get_param('exclude_node_id')) : array(),
			'exclude_entry_id' => ( $this->get_param('exclude_entry_id') ) ? explode('|', $this->get_param('exclude_entry_id')) : array(),
			'siblings_only' => $this->get_param('siblings_only', ''),
			'timestamp' => (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now,
			'show_future_entries' => $this->get_param('show_future_entries', 'no'),
			'show_expired' => $this->get_param('show_expired', 'no'),
			'template_path' => $this->get_param('template_path', ''),
			'use_custom_url' => $this->get_param('use_custom_url', 'no'),
			'parents' => array(),
			'list_type' => $this->get_param('list_type', 'ul'),
			'field_keys' => array(),
			'include_ul' => $this->get_param('include_ul', 'yes')
		);
		// --------------------------------------------------



		
		// per-level parameters / Cheers Rob Sanchez (@_rsan)
		// --------------------------------------------------
		if(is_array(ee()->TMPL->tagparams))
		{
			foreach (ee()->TMPL->tagparams as $key => $value)
			{
				if (strncmp($key, 'ul_css_id:level_', 16) === 0)
				{
					$params[$key] = $value;
				}
				elseif (strncmp($key, 'ul_css_class:level_', 19) === 0)
				{
					$params[$key] = $value;
				}
				elseif (strncmp($key, 'li_css_class:level_', 19) === 0)
				{
					$params[$key] = $value;
				}
			}
		}
		// --------------------------------------------------




		// load what we need
		$tree = ee()->taxonomy->get_tree();
		ee()->taxonomy->get_nodes(); // loads the session array with node data

		// setup default values for our taxonomy custom fields
		// --------------------------------------------------
		if(!empty($tree['fields']) && is_array($tree['fields']))
		{
			foreach($tree['fields'] as $field)
			{
				$params['field_keys'][$field['name']] = '';
				$params['field_keys'][$field['name'].'_type'] = $field['type'];
				$params['field_keys'][$field['name'].'_label'] = $field['label'];
			}
		}
		// --------------------------------------------------

		// getting root node by url_title?
		if($params['root_node_url_title'])
		{
			$params['root_node_entry_id'] = ee()->taxonomy->entry_id_from_url_title($params['root_node_url_title']);
		}

		// Assertain current node
		// --------------------------------------------------
		$this_node = ''; // assume we don't know current node
		$node_cache = (isset($this->cache['trees'][$tree_id]['nodes'])) 
						? $this->cache['trees'][$tree_id]['nodes'] : ''; // shortcut

		// by entry id
		if($entry_id)
		{

			if(isset($node_cache['by_entry_id'][ $entry_id ]))
    		{
    			$this_node = (isset($node_cache['by_node_id'][ $node_cache['by_entry_id'][ $entry_id ] ])) 
    							? $node_cache['by_node_id'][ $node_cache['by_entry_id'][ $entry_id ] ] 
    								: '';
    								
    			$params['node_id'] = $this_node['node_id'];
    		}
    		elseif($params['require_valid_entry_id'] == "yes" && !isset($node_cache['by_entry_id'][ $entry_id ]))
    		{
    			// bail out if our param require_valid_entry_id is set to yes
				// and we don't have an entry_id
				return '';
    		}

		}
		// by node id
		elseif($node_id)
		{
			$this_node = (isset($node_cache['by_node_id'][$node_id])) ? $node_cache['by_node_id'][$node_id] : '';
			$params['node_id'] = (isset($this_node['node_id'])) ? $this_node['node_id'] : '';
		}
		// by url matching
		elseif(isset($node_cache['by_node_id']) && is_array($node_cache['by_node_id']))
		{
			foreach($node_cache['by_node_id'] as $key => $cached_node)
			{
				if($cached_node['url'] == current_url())
				{
					$this_node = $node_cache['by_node_id'][$key];
					$params['node_id'] = $this_node['node_id'];
					break;
				}

			}
		}
		// --------------------------------------------------

		

		// Get parents if current node isn't root 
		// --------------------------------------------------
		if($this_node && $this_node['lft'] != '')
		{
			$params['parents'] = ee()->taxonomy->get_all_parents( $this_node['lft'], $this_node['rgt'] );
		}
		// --------------------------------------------------


		// do we have a tree array
		if(is_array($tree['taxonomy']))
		{

			$tree = $tree['taxonomy'];

			// auto expand requires us to find lft/right of all parents
			// @todo: should just refactor this by expanding the $params['parents'] array.
			if($params['auto_expand'] == 'yes')
			{
				$this->_extract_parent_data($tree, $params);
			}




			// are we starting somewhere down the tree?
			// --------------------------------------------------
			if($params['active_branch_start_level'] > 0 && $this_node != '')
			{	

				$items = array();
				ee()->taxonomy->flatten_tree( $tree, $params['active_branch_start_level'], $items );

				foreach($items as $item)
				{
					if(ee()->taxonomy->find_in_subset($item, $this_node['node_id'], $params['depth'], 'id'))
					{
						$tree = array($item);
						break;
					}
					else
					{
						$tree = array();
					}
				}
			}
			// --------------------------------------------------


			$tree_id = ee()->taxonomy->tree_id; // shortcut
			$tree_cache = (isset($this->cache['trees'][$tree_id])) ? $this->cache['trees'][$tree_id] : ''; // shortcut


			// --------------------------------------------------
			// are we starting with a root node node_id that presumably isn't the root node?
			// query to get the root 
			if($params['root_node_id'])
			{	
				if(isset($tree_cache['nodes']['by_node_id'][ $params['root_node_id'] ]))
				{
					$node = $tree_cache['nodes']['by_node_id'][ $params['root_node_id'] ];
				}
				else
				{
					$node = ee()->taxonomy->get_node($params['root_node_id'], 'node_id');
				}
				
				$tree = ee()->taxonomy->get_tree_taxonomy($node);
			}
			// are we starting with a root node entry_id?
			// query to get the root 
			elseif($params['root_node_entry_id'])
			{	
				if(isset($tree_cache['nodes']['by_entry_id'][ $params['root_node_entry_id'] ]))
				{
					$node_id = $tree_cache['nodes']['by_entry_id'][ $params['root_node_entry_id'] ];

					if(isset($tree_cache['nodes']['by_node_id'][ $node_id ]))
					{
						$node = $tree_cache['nodes']['by_node_id'][ $node_id ];
					}

					$node = $tree_cache['nodes']['by_node_id'][ $node['node_id'] ];
				}
				else
				{
					$node = ee()->taxonomy->get_node($params['root_node_entry_id'], 'entry_id');
				}
				
				$tree = ee()->taxonomy->get_tree_taxonomy($node);
			}
			// starting with a lft value that isn't the root's?
			elseif($params['root_lft'] != 1)
			{
				$node = ee()->taxonomy->get_node($params['root_lft'], 'lft');
				$tree = ee()->taxonomy->get_tree_taxonomy($node);
			}
			// --------------------------------------------------



			// --------------------------------------------------
			// tally ho.
			$r = $this->_build_nav( ee()->TMPL->tagdata, $tree, $params);
			// --------------------------------------------------


		}
		else
		{
			return '';
		}

		$this->cache['first_pass'] = 1;

		// reset the node_count as multiple trees may be output
		if (isset(ee()->session->cache['taxonomy_node_count'])){ee()->session->cache['taxonomy_node_count'] = 1;}

		if($r)
		{
			$r = $params['html_before'].$r.$params['html_after'];
		}

		return $r;

	}

	public function next_node()
	{
		return $this->_sibling_node('next');
	}

	public function prev_node()
	{
		return $this->_sibling_node('prev');
	}

	// ----------------------------------------------------------------

	private function _sibling_node($direction)
	{
		
		$tree_id 		= $this->_get_this('tree_id');
		$entry_id 		= $this->_get_this('entry_id');

		// no tree or no entry && no node_id no partay.
		if(!$tree_id || (!$entry_id && !$entry_id))
			return '';

		if( ee()->taxonomy->set_table( $tree_id ) === FALSE )
		{
			ee()->TMPL->log_item("TAXONOMY:NAV: Returning NULL; Tree requested does not exist.");
			return '';
		} 

		// load what we need from the tree structure
		$tree = ee()->taxonomy->get_tree();
		ee()->taxonomy->get_nodes(); // loads the session array with node data

		$this_node = array();
		$next_node = array();
		$vars = array();

		// does the node we're declaring exist?
		if(isset($tree['nodes']['by_node_id']) && is_array($tree['nodes']['by_node_id']))
		{
			foreach($tree['nodes']['by_node_id'] as $key => $node)
			{
				// find our current node's data
				if($node['entry_id'] == $entry_id)
				{
					$this_node = $tree['nodes']['by_node_id'][$key];
					break;
				}
			}
			if($this_node)
			{
				// get the direction
				if($direction == 'next')
				{
					$key = 'lft';
					$val = $this_node['rgt']+1;
				}
				else // prev
				{
					$key = 'rgt';
					$val = $this_node['lft']-1;
				}

				// loop again and find what we're after
				foreach($tree['nodes']['by_node_id'] as $node)
				{
					if(isset($node[$key]) && $node[$key] == $val)
					{
						$next_node = $node;
						break;
					}
				}
			}
			
		}

		if($next_node)
		{
			foreach($next_node as $key => $val)
			{
				if($key == 'field_data' && $val != '')
				{
					 $val = array($val);
				}
				$vars[$direction.'_node_'.$key] = $val;
			}
		}

		if($vars)
		{
			return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array($vars));
		}
		else
		{
			return '';
		}
		
	}

	// ----------------------------------------------------------------

	public function get_sibling_ids()
	{
		return $this->sibling_entry_ids();
	}

	// return a pipe delimited string of entry_ids which are siblings to the node given.
	public function sibling_entry_ids()
	{

		$tree_id 		= $this->_get_this('tree_id');
		$entry_id 		= $this->_get_this('entry_id');
		$include_current = $this->get_param('include_current', 'no');

		// no tree, no entry, no partay.
		if(!$tree_id || !$entry_id)
			return '';

		if( ee()->taxonomy->set_table( $tree_id ) === FALSE )
		{
			ee()->TMPL->log_item("TAXONOMY:NAV: Returning NULL; Tree requested does not exist.");
			return '';
		} 

		$r = array();

		// load what we need from the tree structure
		$tree = ee()->taxonomy->get_tree();
		ee()->taxonomy->get_nodes(); // loads the session array with node data

		$siblings = ee()->taxonomy->get_siblings($entry_id);

		// we have sibling nodes
		if(count($siblings))
		{	
			// build our array of sibling entry ids
			foreach($siblings as $sibling)
			{
				$r[ $sibling['entry_id'] ] = $sibling['entry_id'];
			}
			// get rid of current node if it's not wanted
			if($include_current != 'yes')
			{
				unset( $r[$entry_id] );
			}
		}

		return implode('|', $r);

	}

	// ----------------------------------------------------------------
	
	public function entries()
	{	
		
		$tree_id 		= $this->_get_this('tree_id');

		// no tree, no partay.
		if(!$tree_id)
			return '';

		if( ee()->taxonomy->set_table( $tree_id ) === FALSE )
		{
			ee()->TMPL->log_item("TAXONOMY:NAV: Returning NULL; Tree requested does not exist.");
			return '';
		} 

		$entry_id 		= $this->_get_this('entry_id');
		$node_id 		= $this->_get_this('node_id');

		// single vars swapped out with the tx: prefix
		if(is_array(ee()->TMPL->var_single))
		{
			foreach(ee()->TMPL->var_single as $key => $var)
			{
				$new_key = str_replace('tx:', '', $key);
				ee()->TMPL->var_single[$new_key] = $new_key;
				unset(ee()->TMPL->var_single[$key]);
			}
		}

		// this is pretty skanky, leave in for now...
		// @todo test with some tag pairs
		if(is_array(ee()->TMPL->var_pair))
		{
			foreach(ee()->TMPL->var_pair as $key => $var)
			{
				$new_key = str_replace('tx:', '', $key);
				ee()->TMPL->var_pair[$new_key] = $var;
				unset(ee()->TMPL->var_pair[$key]);
			}
		}

		// basic find/replaces
		ee()->TMPL->tagdata = str_replace('tx:', '', ee()->TMPL->tagdata);
		ee()->load->library('taxonomy_entries');

		// load what we need from the tree structure
		$tree = ee()->taxonomy->get_tree();
		ee()->taxonomy->get_nodes(); // loads the session array with node data

		// entries by parent_entry_id
		// ----------------------------------------------------------------
		$parent_entry_id = $this->get_param('parent_entry_id');

		if($parent_entry_id)
		{

			$this_node = ee()->taxonomy->get_node( $parent_entry_id, 'entry_id');
			$node_cache = (isset($this->cache['trees'][$tree_id]['nodes'])) 
						? $this->cache['trees'][$tree_id]['nodes'] : ''; // shortcut
			
			$ids = array();

			if($node_cache)
			{
				if(isset($node_cache['by_node_id']) && is_array($node_cache['by_node_id']))
				{
					foreach($node_cache['by_node_id'] as $node)
					{
						if($this_node['node_id'] == $node['parent'] && $node['entry_id'] != '')
						{	
							$ids[$node['lft']] = $node['entry_id'];
						}
					}
					ksort($ids);
					$ids = implode('|', $ids);
				}
			}

			if(!$ids) return '';

			ee()->TMPL->tagparams['fixed_order'] = $ids;
		}
		// ----------------------------------------------------------------

		// if we're passing a null value into fixed order, EE decides to
		// output all entries, which isn't helpful.
		// if we're getting fixed order, and it's null, bail.
		// --------------------------------------------------
		if(is_array(ee()->TMPL->tagparams))
		{
			foreach (ee()->TMPL->tagparams as $key => $value)
			{
				if(isset(ee()->TMPL->tagparams['fixed_order']) && ee()->TMPL->tagparams['fixed_order'] == '')
				{
					return '';
				}
			}
		}
		// --------------------------------------------------

		return ee()->taxonomy_entries->entries();
	}
	
	// ----------------------------------------------------------------
	

	public function prep_vars()
	{
		$prefix = $this->EE->TMPL->tagparams['var_prefix'];
		return str_replace($prefix, '', $this->EE->TMPL->tagdata);

	}

	public function child_entry_ids()
	{
		$tree_id 		= $this->_get_this('tree_id');

		if(!$tree_id)
			return '';

		if( ee()->taxonomy->set_table( $tree_id ) === FALSE )
		{
			ee()->TMPL->log_item("TAXONOMY:NAV: Returning NULL; Tree requested does not exist.");
			return '';
		} 

		$entry_id 		= $this->_get_this('entry_id');
		$node_id 		= $this->_get_this('node_id');
		
		$this_node = '';

		if($entry_id )
		{
			$this_node = ee()->taxonomy->get_node( $entry_id, 'entry_id');
		}
		elseif($node_id)
		{
			$this_node = ee()->taxonomy->get_node( $node_id, 'node_id');
		}
		else
		{
			return 'asdsa';
		}

		// load what we need from the tree structure
		ee()->taxonomy->get_tree();
		ee()->taxonomy->get_nodes(); // loads the session array with node data

		$node_cache = (isset($this->cache['trees'][$tree_id]['nodes'])) 
						? $this->cache['trees'][$tree_id]['nodes'] : ''; // shortcut

		$ids = array();

		if($node_cache)
		{
			if(isset($node_cache['by_node_id']) && is_array($node_cache['by_node_id']))
			{
				foreach($node_cache['by_node_id'] as $node)
				{
					if($this_node['node_id'] == $node['parent'] && $node['entry_id'] != '')
					{	
						$ids[$node['lft']] = $node['entry_id'];
					}
				}
				ksort($ids);
			}
		}

		return implode('|', $ids);
	}


	public function get_children_ids()
	{
		return $this->child_entry_ids();
	}
	

	// --------------------------------------------------------------------
	//  P R I V A T E   M E T H O D S
	// --------------------------------------------------------------------

	/**
     * Get information about a particular node
     * Set the tree/node for all subsequent tags
	 */	
	private function _get_node($set = TRUE)
	{
		
		$add_globals = $this->get_param('add_globals', 'no');

		// via key/val
		$key = $this->get_param('key');
		$val = $this->get_param('val');
		$var_prefix = $this->get_param('var_prefix', 'this_');

		// set the tree id
		$tree_id = $this->_get_this('tree_id');

		if($tree_id)
			$this->cache['this_node']['tree_id'] = $tree_id;
		else
			return NULL; // no tree_id no partay

		if( ee()->taxonomy->set_table( $tree_id ) === FALSE )
		{
			ee()->TMPL->log_item("TAXONOMY:NAV: Returning NULL; Tree requested does not exist.");
			return '';
		} 
		
		$tree = ee()->taxonomy->get_tree();
		ee()->taxonomy->get_nodes();

		// shortcut ref to our tree's node data
		$nodes = (isset($this->cache['trees'][$tree_id]['nodes']['by_node_id'])) ? $this->cache['trees'][$tree_id]['nodes']['by_node_id'] : array();

		$node_id = $this->_get_this('node_id');
		$entry_id = $this->_get_this('entry_id');

		// set via node_id
		if($node_id)
		{
			if($set === TRUE) $this->cache['this_node']['node_id'] = $node_id;
			$val = $node_id;
			$key = 'node_id';
		}
		elseif($entry_id)
		{
			if($set === TRUE) $this->cache['this_node']['entry_id'] = $entry_id;
			$val = $entry_id;
			$key = 'entry_id';

			$node_id = (isset($this->cache['trees'][$tree_id]['nodes']['by_entry_id'][$entry_id]))
						 ? $this->cache['trees'][$tree_id]['nodes']['by_entry_id'][$entry_id] : '';
		}
		elseif($key && $val)
		{
			foreach($nodes as $node)
			{
				if(isset($node[$key]) && $node[$key] == $val)
				{
					$node_id = $this->cache['this_node']['node_id'] = $node['node_id'];
					break;
				}
			}
		}

		// can't find a node, just bail out
		if(!$node_id)
			return '';

		// is this node a parent of others?
		foreach($nodes as $node)
		{
			if($node['parent'] == $node_id)
			{	
				$this->cache['trees'][$tree_id]['nodes']['by_node_id'][$node_id]['has_children'] = 1;
				break;
			}
		}
				
		if(isset($this->cache['trees'][$tree_id]['nodes']['by_node_id'][$node_id]))
		{
			$node = $this->cache['trees'][$tree_id]['nodes']['by_node_id'][$node_id];
			$vars = array(
				$var_prefix.'tree_id' => $tree_id,
				$var_prefix.'node_id' => (isset($node['node_id'])) ? $node['node_id'] : '',
				$var_prefix.'parent_node_id' => (isset($node['parent'])) ? $node['parent'] : '',
				$var_prefix.'node_lft' => (isset($node['lft'])) ? $node['lft'] : '',
				$var_prefix.'node_rgt' => (isset($node['rgt'])) ? $node['rgt'] : '',
				$var_prefix.'node_label' => (isset($node['label'])) ? $node['label'] : '',
				$var_prefix.'node_entry_id' => (isset($node['entry_id'])) ? $node['entry_id'] : '',
				$var_prefix.'node_template_id' => (isset($node['template_path'])) ? $node['template_path'] : '',
				// $var_prefix.'node_field_data' => (isset($node['field_data'])) ? $node['field_data'] : '',
				$var_prefix.'node_status' => (isset($node['status'])) ? $node['status'] : '',
				$var_prefix.'node_highlight' => (isset($node['highlight'])) ? $node['highlight'] : '',
				$var_prefix.'node_title' => (isset($node['title'])) ? $node['title'] : '',
				$var_prefix.'node_url_title' => (isset($node['url_title'])) ? $node['url_title'] : '',
				$var_prefix.'node_entry_date' => (isset($node['entry_date'])) ? $node['entry_date'] : '',
				$var_prefix.'node_template_id' => (isset($node['template_id'])) ? $node['template_id'] : '',
				$var_prefix.'node_template_group_id' => (isset($node['group_id'])) ? $node['group_id'] : '',
				$var_prefix.'node_template_name' => (isset($node['template_name'])) ? $node['template_name'] : '',
				$var_prefix.'node_template_group_name' => (isset($node['group_name'])) ? $node['group_name'] : '',
				$var_prefix.'node_template_is_site_default' => (isset($node['is_site_default'])) ? $node['is_site_default'] : '',
				$var_prefix.'node_level' => (isset($node['depth'])) ? $node['depth'] : '',
				$var_prefix.'node_level+1' => (isset($node['depth'])) ? $node['depth']+1 : '',
				$var_prefix.'node_level+2' => (isset($node['depth'])) ? $node['depth']+2 : '',
				$var_prefix.'node_level-1' => (isset($node['depth'])) ? $node['depth']-1 : '',
				$var_prefix.'node_level-2' => (isset($node['depth'])) ? $node['depth']-2 : '',
				$var_prefix.'node_has_children' => (isset($node['has_children'])) ? $node['has_children'] : '',
				$var_prefix.'node_url' => (isset($node['url'])) ? $node['url'] : ''
			);

			if(count($tree['fields']))
			{
				foreach ($tree['fields'] as $field) 
				{
					$vars[$var_prefix.$field['name']] = (isset($node['field_data'][$field['name']])) ? $node['field_data'][$field['name']] : '';
				}
			}

			$parent = (isset($this->cache['trees'][$tree_id]['nodes']['by_node_id'][ $node['parent'] ])) 
						? $this->cache['trees'][$tree_id]['nodes']['by_node_id'][ $node['parent'] ] : '';

			$vars += array(
				$var_prefix.'parent_node_id' => (isset($parent['node_id'])) ? $parent['node_id'] : '',
				$var_prefix.'parent_parent_node_id' => (isset($parent['parent'])) ? $parent['parent'] : '',
				$var_prefix.'parent_node_lft' => (isset($parent['lft'])) ? $parent['lft'] : '',
				$var_prefix.'parent_node_rgt' => (isset($parent['rgt'])) ? $parent['rgt'] : '',
				$var_prefix.'parent_node_label' => (isset($parent['label'])) ? $parent['label'] : '',
				$var_prefix.'parent_node_entry_id' => (isset($parent['entry_id'])) ? $parent['entry_id'] : '',
				$var_prefix.'parent_node_template_id' => (isset($parent['template_path'])) ? $parent['template_path'] : '',
				// $var_prefix.'parent_node_field_data' => (isset($parent['field_data'])) ? $parent['field_data'] : '',
				$var_prefix.'parent_node_status' => (isset($parent['status'])) ? $parent['status'] : '',
				$var_prefix.'parent_node_highlight' => (isset($parent['highlight'])) ? $parent['highlight'] : '',
				$var_prefix.'parent_node_title' => (isset($parent['title'])) ? $parent['title'] : '',
				$var_prefix.'parent_node_url_title' => (isset($parent['url_title'])) ? $parent['url_title'] : '',
				$var_prefix.'parent_node_entry_date' => (isset($parent['entry_date'])) ? $parent['entry_date'] : '',
				$var_prefix.'parent_node_template_id' => (isset($parent['template_id'])) ? $parent['template_id'] : '',
				$var_prefix.'parent_node_group_id' => (isset($parent['group_id'])) ? $parent['group_id'] : '',
				$var_prefix.'parent_node_template_name' => (isset($parent['template_name'])) ? $parent['template_name'] : '',
				$var_prefix.'parent_node_template_group_name' => (isset($parent['group_name'])) ? $parent['group_name'] : '',
				$var_prefix.'parent_node_template_is_site_default' => (isset($parent['is_site_default'])) ? $parent['is_site_default'] : '',
				$var_prefix.'parent_node_level' => (isset($parent['depth'])) ? $parent['depth'] : '',
				$var_prefix.'parent_node_level+1' => (isset($parent['depth'])) ? $parent['depth']+1 : '',
				$var_prefix.'parent_node_level+2' => (isset($parent['depth'])) ? $parent['depth']+2 : '',
				$var_prefix.'parent_node_level-1' => (isset($parent['depth'])) ? $parent['depth']-1 : '',
				$var_prefix.'parent_node_level-2' => (isset($parent['depth'])) ? $parent['depth']-2 : '',
				$var_prefix.'parent_node_has_children' => 'yes', // duh
				$var_prefix.'parent_node_url' => (isset($parent['url'])) ? $parent['url'] : ''
			);

			if(count($tree['fields']))
			{
				foreach ($tree['fields'] as $field) 
				{
					$vars[$var_prefix.'parent_'.$field['name']] = (isset($parent['field_data'][$field['name']])) ? $parent['field_data'][$field['name']] : '';
				}
			}

			$tagdata = ee()->TMPL->tagdata;

			if($tagdata)
			{
				return ee()->TMPL->parse_variables_row($tagdata, $vars);
			}

			if($add_globals == 'yes')
			{
				ee()->config->_global_vars = array_merge($vars, ee()->config->_global_vars);
			}

		}	

	}




	// --------------------------------------------------------------------


	/**
	 * _build_nav
	 *
	 * Recursive method which takes the tagdata, tree structure and params and converts to a list
	 *
	 * @return	string
	 */
	private function _build_nav($tagdata, $taxonomy, $params)
	{
		
		$tree_id = ee()->taxonomy->tree_id;

		exit($params['ul_css_class']);


		// filter out nodes we don't want from this level
		$taxonomy = $this->_pre_process_level($taxonomy, $params);

		// flag subsequent requests to this method as false.
		$params['first_pass'] = FALSE;

		$level_count = 1;
		$level_total_count = count($taxonomy);
		$str = '';
		// pre-process the nodes here to make sure they are the ones we want.
		// loop into each node on this level
		foreach($taxonomy as $node)
    	{	

    		if($level_count == 1 && $params['include_ul'] == 'yes' && $params['style'] == 'nested') 
			{

				if(isset($params['ul_css_id:level_'.$node['level']]))
				{
					$params['ul_css_id'] = $params['ul_css_id:level_'.$node['level']]);
				}

				if(isset($params['ul_css_class:level_'.$node['level']]))
				{
					$params['ul_css_class'] = $params['ul_css_class:level_'.$node['level']]);
				}

				$ul_css_id = ($params['ul_css_id'] != '') ? ' id="'.$params['ul_css_id'].'"' : '';
				$ul_css_class = ($params['ul_css_class'] != '') ? ' class="'.$params['ul_css_class'].'"' : '';
				$str = "\n<".$params['list_type'].$ul_css_id.$ul_css_class.'>';

			}
    		// set the default vars
    		$vars = $this->node_vars;

    		if(isset($this->cache['trees'][$tree_id]['nodes']['by_node_id'][ $node['id'] ]))
    		{
    			// get the node attributes
    			$att = $this->cache['trees'][$tree_id]['nodes']['by_node_id'][ $node['id'] ];

    			$active = '';
    			$active_parent = '';

    			

    			// flag the active class
    			if($params['entry_id'] != '' && $params['entry_id'] != "{entry_id}") // there's always some tit.
    			{
    				if( $att['entry_id'] == $params['entry_id'] )
    				{
    					$active = 'active';
    				}
    			}
    			elseif($att['node_id'] == $params['node_id'])
    			{
    				$active = 'active';
    			} 

    			if(in_array($att['node_id'], $params['parents']))
    			{
    				$active_parent = 'active_parent';
    			}

    			$vars = array(
					'node_id' => $att['node_id'],
					'node_title' => $att['label'],
					'node_url' => $att['url'],
					'node_relative_url' => '', // @todo
					'node_active' => $active,
					'node_active_parent' => $active_parent,
					'node_lft' => $att['lft'],
					'node_rgt' => $att['rgt'],
					'node_entry_id' => $att['entry_id'],
					'node_custom_url' => $att['custom_url'],
					// 'node_field_data' => $att['field_data'],
					'node_entry_title' => $att['title'],
					'node_entry_url_title' => $att['url_title'],
					'node_entry_status' => $att['status'],
					'node_entry_entry_date' => $att['entry_date'],
					'node_entry_expiration_date' => $att['expiration_date'],
					'node_entry_template_name' => '', // @todo
					'node_entry_template_group_name' => '', // @todo
					'node_has_children' => (isset($node['children'])) ? 'yes' : 0,
					'node_next_child' => $att['lft']+1,
					'node_level' => $node['level'],
					'node_level_count' => $level_count,
					'node_level_total_count' => $level_total_count,
					'node_indent' => str_repeat(' ', $level_count),
					'node_count' => ee()->session->cache['taxonomy_node_count']++,
					'children' => ''
				);
				
				// add our default field values
				$vars += $params['field_keys'];

				// if values exist, swap 'em out
				if($att['field_data'] != '' && is_array($att['field_data']))
				{
					foreach($att['field_data'] as $key => $field)
					{
						$vars[$key] = $field;
					}
				}

    		}

    		// have we got children, go through this method recursively
    		if((isset($node['children'])))
    		{	
    			ee()->TMPL->log_item("TAXONOMY:NAV: processing child nodes");
    			$vars['children'] = $this->_build_nav($tagdata, $node['children'], $params);
    		}
    		
    		// swappy swappy
    		$tmp = ee()->functions->prep_conditionals($tagdata, $vars);
    		$str .= ee()->functions->var_swap($tmp, $vars);

    		// close out our list
    		if($level_count == $level_total_count && $params['include_ul'] == 'yes' && $params['style'] == 'nested') 
			{
				$str .= "\n</".$params['list_type'].'>';
			}

			$level_count++;

    	}

    	// et voila
		return $str;

	}
	// ---------------------------------------------------------------


	// ---------------------------------------------------------------
	// if we're using auto expand, we need to traverse the tree to get level data for the parents
	// @todo - I think this is some horrible shit right here.
	private function _extract_parent_data($tree, $params)
	{
		foreach($tree as $key => $node)
    	{
    		if(isset($this->cache['trees'][ee()->taxonomy->tree_id]['nodes']['by_node_id'][ $node['id'] ]))
    		{
    			$att = $this->cache['trees'][ee()->taxonomy->tree_id]['nodes']['by_node_id'][ $node['id'] ];
	    		foreach($params['parents'] as $parent)
				{
					if($node['id'] == $parent)
					{
						$this->actp_lev[$node['level']]['act_lft']	= $att['lft'];
						$this->actp_lev[$node['level']]['act_rgt']	= $att['rgt'];
					}
				}
				if(!count($params['parents']))
				{
					$this->actp_lev[$node['level']]['act_lft']	= $att['lft'];
					$this->actp_lev[$node['level']]['act_rgt']	= $att['rgt'];
				}
	    	}

	    	if(isset($node['children']) && count($params['parents']))
	    	{
	    		$this->_extract_parent_data($node['children'], $params);
	    	}

    	}

	}
	// ---------------------------------------------------------------



	// ---------------------------------------------------------------
	// filter for status, entry_date, expiration date etc
	private function _pre_process_level($taxonomy, $params)
	{
		$tree_id = ee()->taxonomy->tree_id;

		if(!isset($this->cache['first_pass'])) $this->cache['first_pass'] = 1;



		foreach($taxonomy as $key => $node)
    	{

    		if(isset($this->cache['trees'][$tree_id]['nodes']['by_node_id'][ $node['id'] ]))
    		{
    			$att = $this->cache['trees'][$tree_id]['nodes']['by_node_id'][ $node['id'] ];

    			

				if($node['id'] == $params['node_id']  && $node['level'] != 0)
				{
					$this->act_lev[$node['level']]['act_lft']	= $att['lft'];
					$this->act_lev[$node['level']]['act_rgt']	= $att['rgt'];
				}

    			// taking out the root, or are we requesting a level below the current
    			if( ($node['level'] == 0 || $this->cache['first_pass'] == 1) && $params['display_root'] == "no" && isset($node['children']))
	    		{
	    			$this->cache['first_pass'] = 0;
	    			return (isset($taxonomy[0]['children'])) ? $this->_pre_process_level($taxonomy[0]['children'], $params) : array();
	    		}

	    		// filter statuses
    			// --------------------------------------------------
    			if($params['depth'] < $node['level'])
    			{
					unset($taxonomy[$key]);
    			}
    			// --------------------------------------------------

    			// filter statuses
    			// --------------------------------------------------
    			if($att['status'] != "" && $params['entry_status'] != array('ALL'))
    			{
    				if( !in_array($att['status'], $params['entry_status']) )
					{
						unset($taxonomy[$key]);
					}
    			}
    			// --------------------------------------------------
    			
				// filter expired entries
				// --------------------------------------------------
				if($att['expiration_date'] != 0)
				{
					if ($att['expiration_date'] < $params['timestamp'] && $params['show_expired'] != "yes") 
					{
						unset($taxonomy[$key]);
					}
				}
				// --------------------------------------------------
				
				// filter future entries
				// --------------------------------------------------
				if($att['entry_date'])
				{
					if ($att['entry_date'] > $params['timestamp'] && $params['show_future_entries'] != "yes") 
					{
						unset($taxonomy[$key]);
					}
				}
				// --------------------------------------------------

				// excluded nodes by node_id
				// --------------------------------------------------
				if( count($params['exclude_node_id']) )
				{
					if(in_array($att['node_id'], $params['exclude_node_id']))
					{
						unset($taxonomy[$key]);
					}
				}
				// --------------------------------------------------

				// excluded nodes by entry_id
				// --------------------------------------------------
				if(count($params['exclude_entry_id']))
				{
					if(in_array($att['entry_id'], $params['exclude_entry_id']))
					{
						unset($taxonomy[$key]);
					}
				}
				// --------------------------------------------------

				// auto expanding of active branch
				// --------------------------------------------------
				if($params['auto_expand'] == 'yes')
				{

					if (
						$node['level'] == 0
						||
						(
							( // are we on a sibling of an active parent?
							isset($this->actp_lev[($node['level']-1)]['act_lft'])
							&& 
							$att['lft'] >= $this->actp_lev[($node['level']-1)]['act_lft']
							&& 
							$att['rgt'] <= $this->actp_lev[($node['level']-1)]['act_rgt']
							)
						||
							( // are we on a sibling of the active
							isset($this->act_lev[($node['level']-1)]['act_lft'])
							&& 
							$att['lft'] >= $this->act_lev[($node['level']-1)]['act_lft']
							&& 
							$att['rgt'] <= $this->act_lev[($node['level']-1)]['act_rgt']
							)
						)
						|| $node['level'] <= $params['active_branch_start_level']
					)
					{
						// getting farking complicated
					}
					else
					{
						unset($taxonomy[$key]);
					}

				}
				// --------------------------------------------------

				
    		}

    	}

		return $taxonomy;
	}

	// ----------------------------------------------------------------

	/**
     * Get the tree_id, node_id or entry_id from param, or session_cache if param not used.
	 */		 
	private function _get_this($key = 'tree_id')
	{
		// do we have a globally set tree id (if the param is not set
		if($this->get_param($key) == '' && isset($this->cache['this_node'][$key]))
			$id = $this->cache['this_node'][$key];
		else
			$id = $this->get_param($key);

		if($id == '{entry_id}') // there's always some twit.
			return '';

		return $id;
	}


	
}
/* End of file mod.taxonomy.php */
/* Location: /system/expressionengine/third_party/taxonomy/mod.taxonomy.php */