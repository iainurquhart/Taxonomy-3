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

		/*
		3.0 Update requirements
		exp_taxonomy_trees
		====================
		x== add 'name' column - which is a url_title of 'label'
		x== rename 'template_preferences' to 'templates'
		x== rename 'channel_preferences' to 'channels'
		x== rename 'permissions' to 'member_groups'
		x== base64_decode, unserialize 'fields' and json_encode them
		x== rename 'tree_array' to 'taxonomy' and update tree structures

		exp_taxonomy_tree_x
		=====================
		
		*/

		
		if ($current < '3.0') 
		{
			ee()->load->dbforge();
			ee()->load->helper('url');
			ee()->load->model('taxonomy_model', 'taxonomy');

			// ------------------------------------------------------
			// UPDATES TO exp taxonomy_trees
			// ------------------------------------------------------
			// ---------------------------
			// add the name column
			// ---------------------------
			$fields = array(
				'name'	=> array(
					'type' => 'varchar',
					'constraint' => '250'
				)
			);
			ee()->dbforge->add_column('taxonomy_trees', $fields);
			// ---------------------------

			// ---------------------------
			// rename 'template_preferences' to 'templates'
			// ---------------------------
			$fields = array(
				'template_preferences' => array(
					'name' => 'templates',
					'type' => 'varchar',
					'constraint' => '250', 
					'default' => 'all'
				),
			);
			ee()->dbforge->modify_column('taxonomy_trees', $fields);
			// ---------------------------

			// ---------------------------
			// rename 'channel_preferences' to 'channels'
			// ---------------------------
			$fields = array(
				'channel_preferences' => array(
					'name' => 'channels',
					'type' => 'varchar',
					'constraint' => '250', 
					'default' => 'all'
				),
			);
			ee()->dbforge->modify_column('taxonomy_trees', $fields);
			// ---------------------------

			// ---------------------------
			// rename 'permissions' to 'member_groups'
			// ---------------------------
			$fields = array(
				'permissions' => array(
					'name' => 'member_groups',
					'type' => 'varchar',
					'constraint' => '250'
				),
			);
			ee()->dbforge->modify_column('taxonomy_trees', $fields);
			// ---------------------------

			// ---------------------------
			// rename 'tree_array' to 'taxonomy'
			// ---------------------------
			$fields = array(
				'tree_array' => array(
					'name' => 'taxonomy',
					'type' => 'longtext'
				),
			);
			ee()->dbforge->modify_column('taxonomy_trees', $fields);
			// ---------------------------

			// update data stored from encoded serialized to json
			// and insert missing values to new columns
			$trees = ee()->db->get('taxonomy_trees')->result_array();
			foreach($trees as $tree)
			{

				// ---------------------------
				// add the depth, parent and type columns
				// ---------------------------
				$fields = array(
					'depth'	=> array(
						'type' => 'mediumint',
						'constraint'	=> '8',
						'unsigned'	=>	TRUE
					),
					'parent' => array(
						'type' => 'mediumint',
						'constraint'	=> '8',
						'unsigned'	=>	TRUE
					),
					'type' => array(
						'type' => 'varchar', 
						'constraint' => '250', 
						'null' => TRUE
					)
				);

				ee()->dbforge->add_column('taxonomy_tree_'.$tree['id'], $fields);
				// ---------------------------

				ee()->taxonomy->set_table( $tree['id'] );
				$taxonomy = json_encode( ee()->taxonomy->get_tree_taxonomy() );

				// modify each tree table column names
	            ee()->db->where('id', $tree['id']);

	            // if fields has data, decode it so it can be json encoded
	            $field_data = ($tree['fields'] != '') ? unserialize(base64_decode($tree['fields'])) : '';

	            $data = array(
	            	'name' => url_title($tree['label'], '_', TRUE),
	            	'fields' => ($field_data) ? json_encode($field_data) : '',
	            	'taxonomy' => $taxonomy 
	            );
	            
				ee()->db->update('taxonomy_trees', $data );

				// update node values
				// add depth and parent data
				$nodes = ee()->db->get('taxonomy_tree_'.$tree['id'])->result_array();

				$a_data = $this->_build_adjacency_data($tree['id']);

				foreach($nodes as $node)
				{

					// we have to establish the depth, so find parents
					ee()->db->select('node_id')
						 ->where('lft <', $node['lft'])
						 ->where('rgt >', $node['rgt'])
						 ->group_by("lft")
						 ->order_by("lft", "asc");

					$parents = ee()->db->get( 'taxonomy_tree_'.$tree['id'] )->result_array();

					// extract existing field data if it exists
					$field_data = ($node['field_data'] != '') ?  unserialize(base64_decode($node['field_data'])) : '';

					// extract node types
					$type = array();
					if($node['entry_id']) $type[] = 'entry';
					if($node['template_path']) $type[] = 'template';
					if($node['custom_url'] && $node['custom_url'] != '[page_uri]') $type[] = 'custom';

					$data = array(
		            	'field_data' => ($field_data) ? json_encode($field_data) : '',
		            	'type' => implode('|', $type),
		            	'custom_url' => ($node['custom_url'] == '[page_uri]') ? '' : $node['custom_url'],
		            	'parent' => (isset($a_data[ $node['node_id'] ])) ? $a_data[ $node['node_id'] ] : 0,
		            	'depth' => count($parents)
		            );
		            ee()->db->where('node_id', $node['node_id']);
					ee()->db->update('taxonomy_tree_'.$tree['id'], $data );
				}


			}

		}

		// includes a new 'depth' key to the taxonomy tree array
		// 'level' is relative to root, can be different to actual 'depth'
		// which can be different if querying a tree from somewhere other than root. 
		if ($current < '3.0.8') 
		{
			ee()->load->model('taxonomy_model', 'taxonomy');
			$trees = ee()->db->get('taxonomy_trees')->result_array();
			foreach($trees as $tree)
			{
				$data = array();
				ee()->taxonomy->set_table( $tree['id'] );
				$data['taxonomy'] = json_encode( ee()->taxonomy->get_tree_taxonomy() );

				ee()->db->where('id', $tree['id']);
				ee()->db->update( 'taxonomy_trees', $data );
			}
		}



		// If you have updates, drop 'em in here.
		return TRUE;

		

	}

	private function _build_adjacency_data($tree_id = 0)
	{

		if(!$tree_id)
			return;
		
		$data = array();
		
		$query = 'SELECT
			node.label AS name,
           	node.node_id,
           	parent.label AS parent_name,
            parent.node_id AS parent_node_id
            
			FROM exp_taxonomy_tree_'.$tree_id.' AS node
			
           	LEFT JOIN exp_taxonomy_tree_'.$tree_id.' AS parent
           	
           	ON parent.lft = (
                SELECT           MAX(rel.lft)
                FROM             exp_taxonomy_tree_'.$tree_id.' AS rel
                WHERE            rel.lft < node.lft AND rel.rgt > node.rgt
            )

			ORDER BY node.lft ASC;';

		$query = ee()->db->query($query);	
		
		foreach($query->result_array() as $row)
		{
			$data[ $row['node_id'] ] = $row['parent_node_id'];
		}
		
		return $data;
	
	}
	
}
/* End of file upd.taxonomy.php */
/* Location: /system/expressionengine/third_party/taxonomy/upd.taxonomy.php */