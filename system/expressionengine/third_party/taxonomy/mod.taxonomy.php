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
	

	// --------------------------------------------------------------------
	//  M E T H O D S
	// --------------------------------------------------------------------
	

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

		$this->EE->load->model('taxonomy_model', 'taxonomy');
		$this->EE->load->helper('url');
		
		if( $this->EE->taxonomy->set_table( $tree_id ) === FALSE )
		{
			$this->EE->TMPL->log_item("TAXONOMY:NAV: Returning NULL; Tree requested does not exist.");
			return '';
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
			'url_title' => $this->get_param('url_title'),
			'auto_expand' => $this->get_param('auto_expand', 'no'),
			'style' => $this->get_param('style', 'nested'),
			'path' => '',
			'entry_status' => ($this->get_param('entry_status') ) ? explode('|', $this->get_param('entry_status')) : array('open'),
			'active_branch_start_level' => $this->get_param('active_branch_start_level', 0),
			'node_active_class' => $this->get_param('node_active_class', 'active'),
			'site_id' => $this->get_param('site_id', $this->EE->config->item('site_id')),
			'require_valid_entry_id' => $this->get_param('require_valid_entry_id', FALSE),
			'html_before' => $this->get_param('html_before', ''),
			'html_after' => $this->get_param('html_after', ''),
			'wrapper_ul' => $this->get_param('wrapper_ul', 'yes'),
			'node_id' => $this->get_param('node_id', ''),
			'exclude_node_id' => ( $this->get_param('exclude_node_id') ) ? explode('|', $this->get_param('exclude_node_id')) : array(),
			'exclude_entry_id' => ( $this->get_param('exclude_entry_id') ) ? explode('|', $this->get_param('exclude_entry_id')) : array(),
			'siblings_only' => $this->get_param('siblings_only', ''),
			'timestamp' => ($this->EE->TMPL->cache_timestamp != '') ? $this->EE->TMPL->cache_timestamp : $this->EE->localize->now,
			'show_future_entries' => $this->get_param('show_future_entries', 'no'),
			'show_expired' => $this->get_param('show_expired', 'no'),
			'template_path' => $this->get_param('template_path', ''),
			'use_custom_url' => $this->get_param('use_custom_url', 'no'),
			'li_has_children_class' => $this->get_param('li_has_children_class', 'has_children'),
			'parents' => array(),
			'list_type' => $this->get_param('list_type', 'ul'),
			'field_keys' => array()
		);
		// --------------------------------------------------

		// per-level parameters / Cheers Rob Sanchez (@_rsan)
		// --------------------------------------------------
		foreach ($this->EE->TMPL->tagparams as $key => $value)
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
		// --------------------------------------------------

		// load what we need
		$tree = $this->EE->taxonomy->get_tree();
		$this->EE->taxonomy->get_nodes(); // loads the session array with node data

		// setup default values for our taxonomy custom fields
		// --------------------------------------------------
		if($tree['fields'] != '')
		{
			$tree['fields'] = json_decode($tree['fields'], TRUE);
			foreach($tree['fields'] as $field)
			{
				$params['field_keys'][$field['name']] = '';
				$params['field_keys'][$field['name'].'_type'] = $field['type'];
				$params['field_keys'][$field['name'].'_label'] = $field['label'];
			}
		}

		// --------------------------------------------------

		// Assertain current node
		// --------------------------------------------------
		$this_node = ''; // assume we don't know current node
		$node_cache = (isset($this->cache['trees'][$tree_id]['nodes'])) ? $this->cache['trees'][$tree_id]['nodes'] : ''; // shortcut

		// by entry id
		if($entry_id)
		{

			if(isset($node_cache['by_entry_id'][ $entry_id ]))
    		{
    			$this_node = (isset($node_cache['by_node_id'][ $node_cache['by_entry_id'][ $entry_id ] ])) 
    							? $node_cache['by_node_id'][ $node_cache['by_entry_id'][ $entry_id ] ] 
    								: '';
    			$params['entry_id'] = $this_node['entry_id'];
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
			$params['node_id'] = $this_node['node_id'];
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
			$params['parents'] = $this->EE->taxonomy->get_all_parents( $this_node['lft'], $this_node['rgt'] );
		}
		// --------------------------------------------------

		// do we have a tree array
		if(!is_array($tree['taxonomy']) && $tree['taxonomy'] != '')
		{

			$tree = json_decode($tree['taxonomy'], TRUE);

			// auto expand requires us to find lft/right of all parents
			if($params['auto_expand'] == 'yes')
			{
				$this->_extract_parent_data($tree, $params);
			}

			// are we starting somewhere down the tree?
			// --------------------------------------------------
			if($params['active_branch_start_level'] > 0 && $this_node != '')
			{	

				$items = array();
				$this->EE->taxonomy->flatten_tree( $tree, $params['active_branch_start_level'], $items );

				foreach($items as $item)
				{
					if($this->EE->taxonomy->find_in_subset($item, $this_node['node_id'], $params['depth'], 'id'))
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

			// are we starting with a root node node_id/entry_id/lft?
			// query to get the root 
			if($params['root_node_id'])
			{	
				if(isset($this->cache['trees'][$this->EE->taxonomy->tree_id]['nodes']['by_node_id'][ $params['root_node_id'] ]))
				{
					$node = $this->cache['trees'][$this->EE->taxonomy->tree_id]['nodes']['by_node_id'][ $params['root_node_id'] ];
				}
				else
				{
					$node = $this->EE->taxonomy->get_node($params['root_node_id'], 'node_id');
				}
				
				$tree = $this->EE->taxonomy->get_tree_taxonomy($node);
			}

			// are we starting with a root node node_id/entry_id/lft?
			// query to get the root 
			elseif($params['root_node_entry_id'])
			{	
				if(isset($this->cache['trees'][$this->EE->taxonomy->tree_id]['nodes']['by_entry_id'][ $params['root_node_entry_id'] ]))
				{
					$node_id = $this->cache['trees'][$this->EE->taxonomy->tree_id]['nodes']['by_entry_id'][ $params['root_node_entry_id'] ];

					if(isset($this->cache['trees'][$this->EE->taxonomy->tree_id]['nodes']['by_node_id'][ $node_id ]))
					{
						$node = $this->cache['trees'][$this->EE->taxonomy->tree_id]['nodes']['by_node_id'][ $node_id ];
					}

					$node = $this->cache['trees'][$this->EE->taxonomy->tree_id]['nodes']['by_node_id'][ $node['node_id'] ];
				}
				else
				{
					$node = $this->EE->taxonomy->get_node($params['root_node_entry_id'], 'entry_id');
				}
				
				$tree = $this->EE->taxonomy->get_tree_taxonomy($node);
			}

			elseif($params['root_lft'] != 1)
			{
				$node = $this->EE->taxonomy->get_node($params['root_lft'], 'lft');
				$tree = $this->EE->taxonomy->get_tree_taxonomy($node);
			}

			$r = $this->_build_nav( $this->EE->TMPL->tagdata, $tree, $params);
		}
		else
		{
			return '';
		}

		$this->cache['first_pass'] = 1;

		return $r;

	}

	private function _build_nav($tagdata, $taxonomy, $params)
	{
		
		$tree_id = $this->EE->taxonomy->tree_id;

		// filter out nodes we don't want from this level
		$taxonomy = $this->_pre_process_level($taxonomy, $params);

		// flag subsequent requests to this method as false.
		$params['first_pass'] = FALSE;

		$level_count = 0;
		$level_total_count = count($taxonomy);
		$str = '';
		// pre-process the nodes here to make sure they are the ones we want.
		// loop into each node on this level
		foreach($taxonomy as $node)
    	{	

    		if($level_count == 0) $str = '<'.$params['list_type'].'>';
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
					'node_field_data' => $att['field_data'],
					'node_entry_title' => $att['title'],
					'node_entry_url_title' => $att['url_title'],
					'node_entry_status' => $att['url'],
					'node_entry_entry_date' => $att['status'],
					'node_entry_expiration_date' => $att['expiration_date'],
					'node_entry_template_name' => '',
					'node_entry_template_group_name' => '',
					'node_has_children' => (isset($node['children'])) ? 1 : 0,
					'node_next_child' => $att['lft']+1,
					'node_level' => $node['level'],
					'node_level_count' => $level_count++,
					'node_level_total_count' => $level_total_count,
					'node_indent' => str_repeat('', $level_count),
					'children' => ''
				);
				
				// add our default field values
				$vars += $params['field_keys'];

				// if values exist, swap 'em out
				if($att['field_data'] != '' && !is_array($att['field_data']))
				{
					$att['field_data'] = json_decode($att['field_data'], TRUE);
					foreach($att['field_data'] as $key => $field)
					{
						$vars[$key] = $field;
					}
				}

    		}

    		if((isset($node['children'])))
    		{	
    			$this->EE->TMPL->log_item("TAXONOMY:NAV: processing child nodes");
    			$vars['children'] = $this->_build_nav($tagdata, $node['children'], $params);
    		}
    		
    		$tmp = $this->EE->functions->prep_conditionals($tagdata, $vars);
    		$str .= $this->EE->functions->var_swap($tmp, $vars);

    		if($level_count == $level_total_count) $str .= '</'.$params['list_type'].'>';;

    	}

		return $str;
	}



	// if we're using auto expand, we need to traverse the tree to get level data for the parents
	// @todo - this is some horrible shit right here.
	private function _extract_parent_data($tree, $params)
	{
		foreach($tree as $key => $node)
    	{
    		if(isset($this->cache['trees'][$this->EE->taxonomy->tree_id]['nodes']['by_node_id'][ $node['id'] ]))
    		{
    			$att = $this->cache['trees'][$this->EE->taxonomy->tree_id]['nodes']['by_node_id'][ $node['id'] ];
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


	// filter for status, entry_date, expiration date etc
	private function _pre_process_level($taxonomy, $params)
	{
		$tree_id = $this->EE->taxonomy->tree_id;

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
     * The the url for a node from tree_id + (entry_id or node_id)
	 */	
	function node_url()
	{
		$tree_id 		= $this->_get_this('tree_id');
		$entry_id 		= $this->_get_this('entry_id');
		$node_id 		= $this->_get_this('node_id');
		$use_relative 	= $this->get_param('use_relative', FALSE);
		$url_base 		= ($use_relative === FALSE) ? $this->site_url : '';

		if(!$tree_id || (!$node_id && !$entry_id))
			return NULL;

		$this->EE->load->model('taxonomy_model');

		$this->EE->taxonomy_model->cache_node_urls( $tree_id );

		// via entry_id
		if($entry_id && isset($this->cache['trees'][$tree_id]['node_urls']['via_entry_id'][$entry_id]))
		{
			return $url_base.$this->cache['trees'][$tree_id]['node_urls']['via_entry_id'][$entry_id];
		}

		// via node_id
		if($node_id && isset($this->cache['trees'][$tree_id]['node_urls']['via_node_id'][$node_id]))
		{
			return $url_base.$this->cache['trees'][$tree_id]['node_urls']['via_node_id'][$node_id];
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

		if(!$tree_id || (!$node_id && !$entry_id))
			return NULL;

		$this->EE->load->model('nested_set_model');
		$this->EE->load->model('taxonomy_model');
		$tmpl = $this->EE->TMPL;
		$tagdata = $tmpl->tagdata;
		$nested_set = $this->EE->nested_set_model;
		$taxonomy   = $this->EE->taxonomy_model;
		$nested_set->set_table( $tree_id );

		$wrap_li 		= ($tmpl->fetch_param('wrap_li') == 'yes') ? TRUE : FALSE;
		$display_root 	= $tmpl->fetch_param('display_root');
		$delimiter 		= $this->get_param('delimiter', ' &rarr; ');
		$include_here 	= $this->get_param('include_here');
		$titles_only 	= $this->get_param('titles_only');
		$reverse 		= $this->get_param('reverse');
		$title_field 	= $this->get_param('title_field', 'node_title');

		$this->return_data = $here = '';
		$depth = 0;	

		if($entry_id)
			$here = $nested_set->get_node_by_id( $entry_id, 'entry_id' );
		elseif($node_id)
			$here = $nested_set->get_node_by_id( $node_id, 'node_id' );

// Get the path to here.
// --------------------------------------------------
		$path = $nested_set->get_parents_crumbs($here['lft'],$here['rgt']);

		// no path, no here, bail out.
		if(!$here || !$path)
		{
			// cheers @monooso - http://experienceinternet.co.uk/blog/ee-gotchas-nested-no-results-tags/
			$tag_name = 'no_breadcrumb_results';
			$pattern = '#' .LD .'if ' .$tag_name .RD .'(.*?)' .LD .'/if' .RD .'#s';

			if (is_string($tagdata) && is_string($tag_name) && preg_match($pattern, $tagdata, $matches))
			{
				return $matches[1];
			}

		}

// Folks use this for title trails, reversed breadcrumbs
// --------------------------------------------------
		if($reverse == 'yes')
		{
			if($display_root == "no" && isset($path[0]))
				unset($path[0]);
				
			$path = array_reverse($path);
			
			if($include_here != "no")
			{
				$return_data .= $here['label'].' '.$delimiter;
			}
		}

// Loop through each crumb
// --------------------------------------------------
		foreach($path as &$crumb)
		{
			// use a custom field for node_title
			if($title_field != 'node_title')
			{
				$custom_fields = $this->unserialize($crumb['field_data']);
			}

			$crumb['node_label'] = ( isset($custom_fields[$title_field]) ) ? $custom_fields[$title_field] : $crumb['label'];
			$crumb['node_url'] = $taxonomy->build_node_url($crumb);
			$crumb['node_count'] = ++$depth;
			$crumb['total_nodes'] = count($path);
			$crumb['no_breadcrumb_results'] = 0;
		}

		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $path);

		return $this->EE->TMPL->parse_variables($tagdata, $path);
		
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