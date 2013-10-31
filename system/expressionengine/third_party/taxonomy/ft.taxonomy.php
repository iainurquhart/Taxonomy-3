<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// ------------------------------------------------------------------------

/**
 * Taxonomy Fieldtype
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Iain Urquhart
 * @link		http://iain.co.nz
 * @copyright 	Copyright (c) 2012 Iain Urquhart
 * @license   	Commercial, All Rights Reserved: http://devot-ee.com/add-ons/license/taxonomy/
 */

// ------------------------------------------------------------------------

// Include our config
include(PATH_THIRD.'taxonomy/config.php');


class Taxonomy_ft extends EE_Fieldtype {
	
	// --------------------------------------------------------------------
	//  P R O P E R T I E S
	// --------------------------------------------------------------------
	
	public $field_data = array(
		'label' => '',
		'template_path' => '',
		'custom_url' => '',
		'node_id' => '',
		'parent_lft' => '',
		'lft' => '',
		'rgt' => ''
	);

	/**
	 * Info array
	 *
	 * @access     public
	 * @var        array
	 */
	public $info = array(
		'name'    => TAXONOMY_NAME,
		'version' => TAXONOMY_VERSION
	);
	
	
	
	// --------------------------------------------------------------------
	//  M E T H O D S
	// --------------------------------------------------------------------
	
	
	/**
	 * constructor
	 * 
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->site_id = ee()->config->item('site_id');
		$this->cache =& ee()->session->cache['taxonomy_ft_data'];
	}
	
	
	// --------------------------------------------------------------------
	
	
	public function install()
	{
		return array();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * display_field
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function display_field($data)
	{

		
		ee()->lang->loadfile('taxonomy');
		ee()->load->library('table');
		ee()->load->model('taxonomy_model', 'taxonomy');

		$channel_id = ee()->input->get('channel_id');
		$entry_id   = ee()->input->get('entry_id');

		// make sure settings have been set for this channel
		if(isset($this->settings['taxonomy_options']['channels'][$channel_id]) 
			&& $this->settings['taxonomy_options']['channels'][$channel_id] != '')
		{
			ee()->taxonomy->set_table( $this->settings['taxonomy_options']['channels'][$channel_id] );
		}
		// bail out here.
		else
		{
			return lang('tx_field_not_configured');
		}

		$data = (is_array($data)) ? $data : array();

		// editing an entry, do we have a node for this entry
		if($entry_id)
		{
			// get this node's details
			$data = ee()->taxonomy->get_node( $entry_id, 'entry_id' );
			// we have details
			if($data['node_id'] != '')
			{
				// get the parent nodes details
				$parent = ee()->taxonomy->get_node( $data['parent'], 'node_id' );
				$data['parent_lft'] = $parent['lft'];
			}
		}

		// onwards...
		$vars = array();

		// this should only be populated from a validation error on the page
		$vars['data'] 		= array_merge($this->field_data, $data);
		$vars['settings'] 	= $this->settings;
		$vars['nodes']		= array();
		$vars['templates']	= array();
		$vars['tree']		= ee()->taxonomy->get_tree();
		$vars['hide_template'] = TRUE;

		
		// check for field data
		if(isset($data['field_data']) && $data['field_data'] != '')
		{
			$vars['data']['field_data'] = (is_array($data['field_data'])) ? $data['field_data'] : json_decode($data['field_data'], TRUE);
		}
		
		// build our parent select field
		$nodes = ee()->taxonomy->get_flat_tree();

		if(!$nodes)
		{
			return lang('tx_tree_requires_a_root');
		}

		foreach($nodes as $node)
		{
			$vars['nodes'][ $node['lft'] ] = str_repeat('-&nbsp;', $node['level']).$node['label'];
		}

		// do we want the template picker?
		if(isset($this->settings['taxonomy_options']['show_templates'][$channel_id])
			&& $this->settings['taxonomy_options']['show_templates'][$channel_id] == 1)
		{
			$vars['hide_template'] = FALSE;
		}

		// build our template select
		if(count($vars['tree']['templates']))
		{
			$templates = ee()->taxonomy->get_templates();

			foreach($vars['tree']['templates'] as $template)
			{
				if(isset($templates['by_id'][$template]))
				{
					// reduce double slashes doesn't work if all that's passed is '//'
					$templates['by_id'][$template] = ($templates['by_id'][$template] == '/') ? '' : $templates['by_id'][$template];
					$vars['templates'][$template] = $templates['by_id'][$template].'/';
				}
			}
			
		}

		// no selected template, do we have a defualt template set?
		if($vars['data']['template_path'] == '' 
			&& isset($this->settings['taxonomy_options']['default_template'][$channel_id])
				&& $this->settings['taxonomy_options']['default_template'][$channel_id] != '')
		{
			$vars['data']['template_path'] = $this->settings['taxonomy_options']['default_template'][$channel_id];
		}

		return ee()->load->view('field', $vars, TRUE);
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * save
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function save($data)
	{
		// cache for post_save so we have access to the entry_id if it's a new entry
		$this->cache['data'][$this->settings['field_id']] = $data;
		return '';
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * post_save
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	function post_save($data)
	{
	
		$data = $this->cache['data'][$this->settings['field_id']];
		$data['entry_id'] = $this->settings['entry_id'];
		$data['type'][] = 'entry';
		$ext_info = array(
			'update_type' => 'no_node_data'
		);

		if(!isset($data['label']) || !$data['label']) return '';

		ee()->load->model('taxonomy_model', 'taxonomy');
		ee()->taxonomy->set_table( $data['tree_id'] );

		// get the submitted parent node
		$parent = ee()->taxonomy->get_node( $data['parent_lft'] );

		unset($data['parent_lft']); // not needed for insert

		$data['parent'] = $parent['node_id'];

		$parent_depth = (int) $parent['depth'];

		if($parent['node_id'] === '') // this is the root node
		{
			$data['depth'] = 0;
		}
		elseif($parent_depth === 0) // child of the root node
		{
			$data['depth'] = 1;
		}
		else // child of somewhere else in the tree
		{
			$data['depth'] = $parent_depth+1;
		}


		if(isset($data['template_path']) && $data['template_path'] != '')
		{
			$data['type'][] = 'template';
		}

		if(isset($data['custom_url']) && $data['custom_url'] != '')
		{
			$data['type'][] = 'custom';
		}

		if(isset($data['field_data']) && is_array($data['field_data']))
		{
			$data['field_data'] = json_encode($data['field_data']);
		}

		// updating a node?
		if( isset($data['node_id']) && $data['node_id'] != '' )
		{
			$ext_info = array(
				'update_type' => 'node_update',
				'node_data' => $data
			);
			// get existing parent node according to the db
			$node = ee()->taxonomy->get_node( $data['node_id'], 'node_id' );

			// get the old parent according to the db
			$old_parent = ee()->taxonomy->get_node( $node['parent'], 'node_id' );

			// is it different from what the user has submitted?
			if($parent['lft'] != $old_parent['lft'])
			{
				// with nested set it's easier just to delete and re-insert
				// than try some shady ass move. Delete is shady enough, move will
				// blow your mind.
				ee()->taxonomy->delete_node($node['lft']);
				ee()->taxonomy->append_node_last_by_id( $parent['node_id'], $data );

				$ext_info = array(
					'update_type' => 'new_parent',
					'old_parent' => $parent
				);
			}

			ee()->taxonomy->update_node($data['node_id'], $data);
			
		}
		// inserting a node
		else
		{
			$ext_info['update_type'] = 'new_node';
			ee()->taxonomy->append_node_last( $parent['lft'], $data );
		}

		$tree_array = json_encode( ee()->taxonomy->get_tree_taxonomy() );
		ee()->taxonomy->update_taxonomy( $tree_array, 'fieldtype_save', $ext_info);

		return '';
	
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * pre_process
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function pre_process($data)
	{
		return $data;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * replace_tag
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @param	mixed $params = array()
	 * @param	mixed $tagdata = FALSE
	 * @return	void
	 */
	public function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		return '';
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * display_settings
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function display_settings($data)
	{
		ee()->lang->loadfile('taxonomy');
		ee()->load->model('taxonomy_model', 'taxonomy');
		
		$vars = array();

		$vars['channels'] = $this->_get_fieldgroup_channels( ee()->input->get('group_id') );
		$vars['trees'] = ee()->taxonomy->get_trees();
		$vars['tree_options'] = array('' => '--');
		$vars['settings'] = (isset($data['taxonomy_options'])) ? $data['taxonomy_options'] : array();
		$vars['yes_no_options'] = array(0 => lang('no'), 1 => lang('yes'));
		$vars['templates'] = ee()->taxonomy->get_templates();

		foreach( $vars['trees'] as $tree )
		{
			
			$vars['tree_options'][ $tree['id'] ] = $tree['label'];

			$vars['template_options'][ $tree['id'] ] = array('' => '--');
			foreach($tree['templates'] as $template)
			{	
				if($template != '')
				if($template != '' && isset($vars['templates']['by_id'][$template]))
				{
					$vars['template_options'][ $tree['id'] ][$template] = $vars['templates']['by_id'][$template];
				}
			}
			natcasesort($vars['template_options'][ $tree['id'] ]);

		}

		ee()->table->add_row(
			array('data' => ee()->load->view('field_settings', $vars, TRUE), 'colspan' => 2)				
		);
			
	}
		
		
	// --------------------------------------------------------------------
 		
 		
 	/**
	 * save_settings
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function save_settings($data)
	{
		$options = ee()->input->post('options');

		// a bit messy here, prune out all the input data from template select that we don't need.
		foreach($options['channels'] as $channel_id => $tree_id)
		{
			$selected_template = (isset($options['default_template'][$channel_id][$tree_id])) ?
									$options['default_template'][$channel_id][$tree_id] : '';

			$options['default_template'][$channel_id] = $selected_template;
		}
		
		return array(
			'taxonomy_options' => $options,
		);
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Load taxonomy CSS
	 */
	private function _add_taxonomy_css()
	{
		if (! isset(ee()->session->cache['taxonomy']['css_added']) )
		{
			ee()->session->cache['taxonomy']['css_added'] = 1;
			ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.ee()->config->item('theme_folder_url').'third_party/taxonomy_assets/css/taxonomy.css'.'" />');
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * unserialize
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	protected function unserialize($data)
	{
		$data = @unserialize(base64_decode($data));
		
		return (is_array($data)) ? $data : array();
	}

	private function _get_fieldgroup_channels($group_id)
	{
		return ee()->db->get_where('channels', array('field_group' => $group_id))->result_array();
	}
	

}

/* End of file ft.taxonomy.php */
/* Location: ./system/expressionengine/third_party/taxonomy/ft.taxonomy.php */