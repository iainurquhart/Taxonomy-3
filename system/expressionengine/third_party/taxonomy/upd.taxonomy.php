<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 
 // include base class
if ( ! class_exists('Taxonomy_base'))
{
	require_once(PATH_THIRD.'taxonomy/base.taxonomy.php');
}

class Taxonomy_upd extends Taxonomy_base {
	
	// ----------------------------------------------------------------
	
	/**
	 * Installation Method
	 *
	 * @return 	boolean 	TRUE
	 */
	public function install()
	{
		
		$mod_data = array(
			'module_name'			=> 'Taxonomy',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> "y",
			'has_publish_fields'	=> 'n'
		);
		
		ee()->db->insert('modules', $mod_data);
		
		ee()->load->dbforge();
		
		$fields = array(

			'id' => array(
				'type' => 'int',
				'constraint' => '10',
				'unsigned' => TRUE,
				'auto_increment' => TRUE
			),

			'site_id' => array(
				'type' => 'int', 
				'constraint' => '10'
			),

			'label'	=> array(
				'type' => 'varchar',
				'constraint' => '250'
			),

			'name'	=> array(
				'type' => 'varchar',
				'constraint' => '250'
			),

			'templates' => array(
				'type' => 'varchar', 
				'constraint' => '250', 
				'default' => 'all'
			),

			'channels' => array(
				'type' => 'varchar', 
				'constraint' => '250', 
				'default' => 'all'
			),

			'member_groups' => array(
				'type' => 'varchar',
				'constraint' => '250'
			),

			'last_updated' => array(
				'type' => 'int', 
				'constraint' => '10'
			),

			'fields' => array(
				'type' => 'text'
			),

			'taxonomy' => array(
				'type' => 'longtext'
			),
			
			'max_depth'  => array(
				'type' => 'int',
				'constraint' => '3',
				'unsigned' => TRUE, 
				'default' => 0
			)
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('id', TRUE);
		ee()->dbforge->create_table('taxonomy_trees');
		unset($fields);
		
		return TRUE;
	}

	// ----------------------------------------------------------------
	
	/**
	 * Uninstall
	 *
	 * @return 	boolean 	TRUE
	 */	
	public function uninstall()
	{
		$mod_id = ee()->db->select('module_id')
								->get_where('modules', array(
									'module_name'	=> 'Taxonomy'
								))->row('module_id');
		
		ee()->db->where('module_id', $mod_id)
					 ->delete('module_member_groups');
		
		ee()->db->where('module_name', 'Taxonomy')
					 ->delete('modules');
		
		// -------------------------------------
		//  Remove our Trees
		// -------------------------------------
		$query = ee()->db->get('exp_taxonomy_trees');
		
		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				ee()->dbforge->drop_table('taxonomy_tree_'.$row['id']);
			}
		}
		
		ee()->dbforge->drop_table('taxonomy_trees');
		
		return TRUE;
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Module Updater
	 *
	 * @return 	boolean 	TRUE
	 */	
	public function update($current = '')
	{
		// If you have updates, drop 'em in here.
		return TRUE;
	}
	
}
/* End of file upd.taxonomy.php */
/* Location: /system/expressionengine/third_party/taxonomy/upd.taxonomy.php */