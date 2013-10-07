<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

if( !function_exists('ee') )
{
	function ee()
	{
		static $EE;
		if ( ! $EE) $EE = get_instance();
		return $EE;
	}
}

/**
 * Taxonomy Extension
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Extension
 * @author		Iain Urquhart
 * @link		http://iain.co.nz
 */

class Taxonomy_ext {
	
	public $settings 		= array();
	public $description		= 'Brings Hierarchy to Channel Entries';
	public $docs_url		= 'http://iain.co.nz/taxonomy';
	public $name			= 'Taxonomy';
	public $settings_exist	= 'n';
	public $version			= '3.0';
	
	private $EE;
	
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	public function __construct($settings = '')
	{
		$this->settings = $settings;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Settings Form
	 *
	 * If you wish for ExpressionEngine to automatically create your settings
	 * page, work in this method.  If you wish to have fine-grained control
	 * over your form, use the settings_form() and save_settings() methods 
	 * instead, and delete this one.
	 *
	 * @see http://expressionengine.com/user_guide/development/extensions.html#settings
	 */
	public function settings()
	{
		return array(
			
		);
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @see http://codeigniter.com/user_guide/database/index.html for
	 * more information on the db class.
	 *
	 * @return void
	 */
	public function activate_extension()
	{
		// Setup custom settings in this array.
		$this->settings = array();

		$hooks = array(
			'entry_submission_end',
			'update_multi_entries_loop',
			'cp_menu_array'
		);

		foreach($hooks as $hook)
		{
			$data = array(
				'class'		=> __CLASS__,
				'method'	=> $hook,
				'hook'		=> $hook,
				'settings'	=> serialize($this->settings),
				'version'	=> $this->version,
				'enabled'	=> 'y'
			);

			ee()->db->insert('extensions', $data);
		}	
		
	}

	public function cp_menu_array($menu)
	{
		// play nice with anyone elses extensions here.
		if (ee()->extensions->last_call !== FALSE)
		{
			$menu = ee()->extensions->last_call;
		}

		// ----------------------------------------------------------------------
		// Make sure we're outputting a nav at all
		// ----------------------------------------------------------------------
		
		// bail here if the nav is disabled via config override.
		// or if the user has access to no modules at all.
		if(
			(ee()->config->item('disable_taxonomy_cp_nav'))
			OR 
			( ! ee()->session->userdata('assigned_modules') || ! ee()->cp->allowed_group('can_access_addons', 'can_access_modules')) 
			&& 
			(ee()->session->userdata('group_id') != 1)
		  )
		{
			return $menu;
		}
		
		if (ee()->session->userdata('group_id') != 1)
		{
			// Has the user got access to the taxonomy module?
			$taxonomy_module_id  = ee()->db->select('module_id')
								  ->where('module_name', 'Taxonomy')
								  ->get('modules')
								  ->row('module_id');
			
			
			if ( ! isset(ee()->session->userdata['assigned_modules'][$taxonomy_module_id]) OR  ee()->session->userdata['assigned_modules'][$taxonomy_module_id] !== TRUE)
			{
				return $menu;
			}
		}

		// so we are outputting the nav

		ee()->lang->loadfile('taxonomy');
		ee()->load->helper('taxonomy');

		$this->base = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taxonomy';
		$this->edit_tree_base = $this->base.AMP.'method=edit_nodes'.AMP.'tree_id=';
		$this->module_label = lang('taxonomy_nav_label');
		
		$t_menu[$this->module_label] = array();
		
		// fetch our trees
		$query = ee()->db->get_where('exp_taxonomy_trees',array('site_id' => ee()->config->item('site_id')));
			
		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				// check permissions for each tree
				if( has_access_to_tree(ee()->session->userdata['group_id'], $row['member_groups']) )
				{	
					// how irritating is the 'nav_' prefix? Very farking irritating.
					ee()->lang->language['nav_taxonomy_'.$row['label']] = $row['label'];
					$t_menu[$this->module_label] += array('taxonomy_'.$row['label'] => $this->edit_tree_base.$row['id']);
				}
			}
		}

		// seperator
		$t_menu[$this->module_label][0] = '----';
		
		// overview item
		$t_menu[$this->module_label] += array('overview' => $this->base);

		// merge this into the nav array, after content.
		return array_insert($menu, $t_menu, 1);

	}

	public function entry_submission_end($submitted_entry_id, $submitted_meta, $submitted_data)
	{
		return '';
	}

	public function update_multi_entries_loop($submitted_id, $submitted_data)
	{
		return '';
	}

	public function sessions_end()
	{
		// Add Code for the sessions_end hook here.  
	}

	// ----------------------------------------------------------------------

	// ----------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	function disable_extension()
	{
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');
	}

	// ----------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return 	mixed	void on update / false if none
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
	}	
	
	// ----------------------------------------------------------------------
}

/* End of file ext.taxonomy.php */
/* Location: /system/expressionengine/third_party/taxonomy/ext.taxonomy.php */