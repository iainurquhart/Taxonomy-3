<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * Taxonomy Base Class
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

class Taxonomy_base
{

	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * Add-on name
	 *
	 * @var        string
	 * @access     public
	 */
	public $name = TAXONOMY_NAME;

	/**
	 * Add-on version
	 *
	 * @var        string
	 * @access     public
	 */
	public $version = TAXONOMY_VERSION;

	/**
	 * URL to module docs
	 *
	 * @var        string
	 * @access     public
	 */
	public $docs_url = TAXONOMY_URL;

	/**
	 * Settings array
	 *
	 * @var        array
	 * @access     public
	 */
	public $settings = array();

	// --------------------------------------------------------------------

	/**
	 * EE object
	 *
	 * @var        object
	 * @access     protected
	 */
	protected $EE;

	/**
	 * Package name
	 *
	 * @var        string
	 * @access     protected
	 */
	protected $package = TAXONOMY_SHORT_NAME;

	/**
	 * Site id shortcut
	 *
	 * @var        int
	 * @access     protected
	 */
	protected $site_id;

	/**
	 * Form base url for module
	 *
	 * @var        string
	 * @access     protected
	 */
	protected $form_base_url;

	/**
	 * Base url for module
	 *
	 * @var        string
	 * @access     protected
	 */
	protected $base_url;

	/**
	 * Site url for module
	 *
	 * @var        string
	 * @access     protected
	 */
	protected $site_url;

	/**
	 * Theme base url for module
	 *
	 * @var        string
	 * @access     protected
	 */
	protected $theme_base_url;

	/**
	 * Data array for views
	 *
	 * @var        array
	 * @access     protected
	 */
	protected $data = array();

	

	// --------------------------------------------------------------------
	// METHODS
	// --------------------------------------------------------------------

	

	/**
	 * Constructor
	 *
	 * @access     public
	 * @return     void
	 */
	public function __construct()
	{

		ee()->load->helper('url');

		$this->site_id = ee()->config->item('site_id');

		// install/update wizard in 2.7.2 chokes as the functions class isn't loaded
		if(method_exists(ee()->functions, 'fetch_site_index'))
		{
			$this->site_url = ee()->functions->fetch_site_index();
		}

		if (! isset(ee()->session->cache['taxonomy']))
		{
			ee()->session->cache['taxonomy'] = array();
		}
		
		$this->cache =& ee()->session->cache['taxonomy'];

		
		// are we in the cp?
		if( ee()->input->get('D') == 'cp' )
		{
			ee()->load->library('table');
			$this->form_base_url 	= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.TAXONOMY_SHORT_NAME;
			$this->base_url		= BASE.AMP.$this->form_base_url;
			$this->theme_base_url  = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES . 'taxonomy_assets/' : $this->EE->config->item('theme_folder_url') . 'third_party/taxonomy_assets/';


			// add our cp js/css
			if( ! isset($this->cache['taxonomy_assets_added']) )
			{
				ee()->cp->add_to_head('
					<link rel="stylesheet" type="text/css" href="'.$this->theme_base_url.'css/taxonomy.css?v'.TAXONOMY_VERSION.'" />
				');

				$this->cache['taxonomy_assets_added'] = 1;
			}

		}

	}
	

	// ----------------------------------------------------------------	
	
	/**
     * Helper function for getting a parameter
	 */		 
	function get_param($key, $default_value = '')
	{
		$val = ee()->TMPL->fetch_param($key);
		
		if($val == '') 
			return $default_value;
		else
			return $val;
	}

	// ----------------------------------------------------------------

	function is_ajax_request()
	{
		if (ee()->input->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest')
		{
			return TRUE;
		}
		return FALSE;
	}
	
}