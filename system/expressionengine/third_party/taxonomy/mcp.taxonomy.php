<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
// ------------------------------------------------------------------------

/**
 * Taxonomy Module Control Panel File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Iain Urquhart
 * @link		http://iain.co.nz
 */

 // include base class
if ( ! class_exists('Taxonomy_base'))
{
	require_once(PATH_THIRD.'taxonomy/base.taxonomy.php');
}

class Taxonomy_mcp extends Taxonomy_base {
	
	public $return_data;

	public $tree_settings = array(
		'id' => '',
		'site_id' => '',
		'label' => '',
		'name' => '',
		'templates' => '',
		'channels' => '',
		'last_updated' => '',
		'fields' => '',
		'member_groups' => '',
		'tree_array' => '',
		'max_depth' => 0
	);

	// ----------------------------------------------------------------

	function __construct() 
	{
		parent::__construct();
		$this->EE->load->model('taxonomy_model', 'taxonomy');
	}
	
	// ----------------------------------------------------------------

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
		$vars = array();
		$vars['trees'] = array();
	
		$trees = $this->EE->taxonomy->get_trees();

		// do we haz trees?
		if( ! $trees )
		{
			return $this->_content_wrapper('newbie', 'tx_welcome', $vars);
		}

		foreach( $trees as $key => $tree)
		{
			$vars['trees'][$key] = $tree;
			$vars['trees'][$key]['edit_tree_link'] 	 = anchor( $this->base_url.AMP.'method=edit_tree'.AMP.'tree_id='.$tree['id'], lang('tx_edit_tree_settings') );
			$vars['trees'][$key]['edit_nodes_link']  = anchor( $this->base_url.AMP.'method=edit_nodes'.AMP.'tree_id='.$tree['id'], $tree['label'] );
			$vars['trees'][$key]['delete_tree_link'] = anchor( $this->base_url.AMP.'method=delete_tree'.AMP.'tree_id='.$tree['id'], '<img src="'.$this->EE->cp->cp_theme_url.'images/icon-delete.png" />' );
		}

		return $this->_content_wrapper('index', 'tx_manage_trees', $vars);	
	}

	// ----------------------------------------------------------------

	/**
	 * Edit / Add Tree
	 *
	 * @return 	void
	 */
	public function edit_tree()
	{

		$vars = array();
		$vars['tree'] = '';

		$tree_id = $this->EE->input->get('tree_id');
		$this->EE->taxonomy->set_table( $tree_id );

		// editing an existing tree
		if($tree_id)
		{
			// get our settings
			$vars['tree'] = $this->EE->taxonomy->get_tree();
			// no settings - no tree, bounce the user
			if( ! $vars['tree'] ) $this->_notify_redirect( 'tx_invalid_tree' );
		}
		else
		{
			$this->EE->cp->add_js_script(
				array(
					'plugin' => array('ee_url_title')
				)
			);
			$this->EE->cp->add_to_head('
				<script type="text/javascript" charset="utf-8">
				// <![CDATA[
					$(document).ready(function() {
						$("#tree_label").bind("keyup keydown", function() {
							$(this).ee_url_title("#tree_name");
						});
					});
				// ]]>
				</script>
			');
		}

		// load up our various tree options (channels, templates, etc)
		$vars['tree_options'] = $this->EE->taxonomy->get_tree_options();

		// adding a new tree
		if($this->EE->input->get('new'))
		{
			$vars['tree'] = $this->tree_settings;
		}

		return $this->_content_wrapper('edit_tree', 'tx_edit_tree', $vars);

	}

	// ----------------------------------------------------------------

	/**
	 * Update a tree
	 *
	 * @return 	void
	 */
	public function update_tree()
	{

		$data = $this->EE->input->post('tree');

		$data['templates'] 		= (isset($data['templates'])) ? implode('|', $data['templates']) : '';
		$data['channels'] 		= (isset($data['channels'])) ? implode('|', $data['channels']) : '';
		$data['member_groups'] 	= (isset($data['member_groups'])) ? implode('|', $data['member_groups']) : '';
		$data['site_id'] = $this->site_id;

		$tree_id = $this->EE->taxonomy->update_tree( $data );

		$ret = ($this->EE->input->post('update_and_return')) ? 
			$this->base_url : 
				$this->base_url.AMP.'method=edit_tree'.AMP.'tree_id='.$tree_id;

		$this->EE->session->set_flashdata('message_success', lang('tx_tree_updated'));
		$this->EE->functions->redirect($ret);

	}

	// ----------------------------------------------------------------

	/**
	 * Delete Tree
	 *
	 * @return 	void
	 */
	public function delete_tree()
	{
		
		$this->EE->taxonomy->set_table( $this->EE->input->get('tree_id') );
		
		// @todo add confirmation of delete
		$this->EE->db->where('id', $this->EE->taxonomy->tree_id);
		$this->EE->db->delete('taxonomy_trees');
		
		// thanks codeigniter for wasting my morning, seems if you add a db prefix to
		// the drop_table command it's just ignored...
		$this->EE->load->dbforge();
		$this->EE->dbforge->drop_table( 'taxonomy_tree_'.$this->EE->taxonomy->tree_id );

		$this->EE->session->set_flashdata('message_success', lang('tx_tree_deleted'));
		$this->EE->functions->redirect($this->base_url);
	}

	// ----------------------------------------------------------------

	/**
	 * Edit Nodes
	 *
	 * @return 	void
	 */
	public function edit_nodes()
	{
		$vars = array();

		$this->EE->taxonomy->set_table( $this->EE->input->get('tree_id') );

		// @todo validate permissions

		$vars['tree'] = $this->EE->taxonomy->get_tree();

		// have we got a taxonomy? 
		// if not bail here and prompt to insert a root node first.
		if( $vars['tree']['taxonomy'] == '')
		{	
			$ret = $this->base_url.AMP.'method=manage_node'.AMP.'tree_id='.$this->EE->taxonomy->tree_id.AMP.'add_root=1';
			$this->EE->functions->redirect($ret);
		}
		else
		{
			$vars['tree']['taxonomy'] = json_decode($vars['tree']['taxonomy'], TRUE);
		}

		// add our required js
		$this->EE->cp->add_js_script(
			array(
				'ui' => array(
					'core', 'widget', 'mouse', 'sortable'
				)
			)
		); 

		$this->EE->cp->load_package_js('jquery.mjs.nestedSortable'); 
		$this->EE->javascript->compile();

		$vars['nodes'] = $this->EE->taxonomy->get_nodes();

		$vars['cp_list'] = $this->_build_cp_list( 
			$vars['nodes']['by_node_id'], 
			$vars['tree']['taxonomy'] 
		);

		return $this->_content_wrapper('edit_nodes', 'tx_edit_nodes', $vars);	
	}

	// ----------------------------------------------------------------

	/**
	 * Manage Node
	 *
	 * @return 	void
	 */
	public function manage_node()
	{
		$vars = array();
		$this->EE->taxonomy->set_table( $this->EE->input->get('tree_id') );

		$vars['tree'] = $this->EE->taxonomy->get_tree();
		$vars['node_id'] = $this->EE->input->get('node_id');
		$vars['templates'] = array();
		$vars['channel_entries'] = array();
		$vars['template_options'] = array();
		$vars['root_insert'] = FALSE;
		$vars['nodes'] = $this->EE->taxonomy->get_flat_tree();
		$vars['this_node'] = $this->EE->taxonomy->get_node_by_id( $vars['node_id'] );

		// do we have templates associated with this tree
		// fetch options for selects
		if($vars['tree']['templates'] != '')
		{
			$vars['all_templates'] = $this->EE->taxonomy->get_templates($this->site_id);
			
			foreach($vars['tree']['templates'] as $template)
			{
				if($template != '')
				{
					$vars['template_options'][$template] = $vars['all_templates']['by_id'][$template];
				}
			}

			natcasesort($vars['template_options']);
		}

		// do we have channels associated with this tree
		// fetch entries for select
		if($vars['tree']['channels'] != '')
		{
			$channels = $this->EE->taxonomy->get_channels();
			$channel_entries = $this->EE->taxonomy->get_entries( $vars['tree']['channels'] );

			if( count($channel_entries) )
			{
				foreach($channel_entries as $entry)
				{
					$vars['channel_entries'][ $entry['entry_id'] ] = '['.$channels[$entry['channel_id']].'] &rarr; '.$entry['title'];
				}

				natcasesort( $vars['channel_entries'] );
			}
		}

		$this->EE->cp->load_package_js('jquery.chosen.min'); 
		$this->EE->javascript->compile();

		if( $this->EE->input->get('add_root') == 1 )
		{	
			$vars['root_insert'] = TRUE;
			$lang_key = 'tx_add_root_node';
		}
		else
			$lang_key = 'tx_manage_node';

		$vars['already_selected_entries'] = $this->cache['trees'][$this->EE->input->get('tree_id')]['nodes']['by_entry_id'];

		return $this->_content_wrapper('manage_node', $lang_key, $vars);
	}

	// ----------------------------------------------------------------

	/**
	 * Update Node
	 *
	 * @return 	void
	 */
	public function update_node()
	{
		
		$node = (array) $this->EE->input->post('node');

		if(!isset($node['type']))
		{
			$node['type'] = array();
		}

		$this->EE->taxonomy->set_table( $node['tree_id'] );

		$tree = $this->EE->taxonomy->get_tree();

		// @todo validate tree

		// do we have a parent? get parent info
		if( isset($node['parent_lft']))
		{
			$parent = $this->EE->taxonomy->get_node( $node['parent_lft'] );
			$node['parent'] = $parent['node_id'];
		}

		// are we editing an existing node?
		if( isset($node['node_id']) && $node['node_id'] != '' )
		{
			$this->EE->taxonomy->update_node( $node['node_id'], $node );
			$msg = lang('tx_node_updated');
		}
		// are we inserting a root?
		elseif( isset($node['is_root']) && $node['is_root'] == 1 )
		{
			$this->EE->taxonomy->insert_root( $node );
			$msg = lang('tx_root_inserted');
		}
		// must be inserting a new node
		else
		{
			$this->EE->taxonomy->append_node_last( $node['parent_lft'], $node );
			$msg = lang('tx_node_added');
		}

		$tree_array = json_encode( $this->EE->taxonomy->get_tree_taxonomy() );
		$this->EE->taxonomy->update_taxonomy( $tree_array );

		$this->EE->session->set_flashdata('message_success', $msg);
		$this->EE->functions->redirect( $this->base_url.AMP.'method=edit_nodes'.AMP.'tree_id='.$node['tree_id'] );


	}

	// ----------------------------------------------------------------

	/**
	 * Reorder Nodes
	 *
	 * @return 	void
	 */
	public function reorder_nodes()
	{

		$this->EE->taxonomy->set_table( $this->EE->input->get_post('tree_id') );

		$last_updated_token = $this->EE->input->get_post('last_updated');
		$taxonomy_data 		= json_decode($this->EE->input->get_post('taxonomy_order'), TRUE);
		$tree_settings 		= $this->EE->taxonomy->get_tree(TRUE);

		// Our tree has been edited since this tree order was sent.
		// bail out of the whole operation.
		if( $last_updated_token != $tree_settings['last_updated'])
		{
			$resp['data'] = 'last_update_mismatch';	
			$this->EE->output->send_ajax_response($resp);
		}

		$this->EE->taxonomy->reorder_nodes($taxonomy_data);

		$tree_array = json_encode( $this->EE->taxonomy->get_tree_taxonomy() );
		$this->EE->taxonomy->update_taxonomy( $tree_array );

		// lets just be sure of timestamps and get the tree settings again.
		unset($tree_settings);
		$tree_settings =  $this->EE->taxonomy->get_tree( TRUE );

		$resp['data'] = 'Node order updated';
		$resp['last_updated'] = $tree_settings['last_updated'];

		$this->EE->output->send_ajax_response( $resp );
	}

	// ----------------------------------------------------------------

	/**
	 * Nuke a node
	 *
	 * @access	public
	 */
	function delete_node()
	{
		$tree_id = $this->EE->input->get_post('tree_id');
		$this->EE->taxonomy->set_table( $tree_id );

		$type = ($this->EE->input->get_post('type')) ? 'delete_branch' : 'delete_node';
		
		$node = $this->EE->taxonomy->get_node( $this->EE->input->get_post('node_id'), 'node_id' );
		$this->EE->taxonomy->{$type}( $node['lft'] );

		$tree_array = json_encode( $this->EE->taxonomy->get_tree_taxonomy() );
		$this->EE->taxonomy->update_taxonomy( $tree_array );

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('node_deleted'));
		$this->EE->functions->redirect($this->base_url.AMP.'method=edit_nodes'.AMP.'tree_id='.$tree_id);
	}


	 // ----------------------------------------------------------------

	/**
	 * Builds the drag drop interface in the cp
	 *
	 * @access     private
	 * @param      nodes array
	 * @param      tree  array
	 * @return     array
	 */

    private function _build_cp_list($nodes, $tree, $ind = '')
	{

		$str = "<ol>";

		// root node is not sortable, so don't include in the <ol>
		if($tree[0]['level'] == 0) $str = "<h3>";
		if($tree[0]['level'] == 1) $str = "<ol id='taxonomy-list'>\n";
    	
    	foreach($tree as $node)
    	{
    		$tree_id 			= (int) $this->EE->input->get('tree_id');
    		$node_id			= $node['id'];
    		
    		$url_title 			= '/'.$nodes[$node_id]['url_title'];
    		$custom_url			= (isset($nodes[$node_id]['custom_url'])) ? $nodes[$node_id]['custom_url'] : '';
    		$children 			= (isset($node['children'])) ? $node['children'] : '';
    		$label				= $nodes[$node_id]['label'];
    		$highlight			= $nodes[$node_id]['highlight'];
    		$status_text		= ($nodes[$node_id]['status'] && $nodes[$node_id]['status'] != 'open') ? ' + '.ucfirst($nodes[$node_id]['status']) : '';
    		$status				= ($nodes[$node_id]['status'] && $nodes[$node_id]['status'] != 'open') ? '<em class="status_indicator" title="'.ucfirst($nodes[$node_id]['status']).'" style="background-color: #'.$highlight.'">['.ucfirst($nodes[$node_id]['status']).']</em>' : '';
    		$site_url			= $this->EE->functions->fetch_site_index();
    		$level 				= $node['level'];
    		
    		$expiration_date 	= (isset($nodes[$node_id]['expiration_date'])) ? $nodes[$node_id]['expiration_date'] : 0;
    		$entry_date 		= (isset($nodes[$node_id]['entry_date'])) ? $nodes[$node_id]['entry_date'] : 0;
    		$now 				= $this->EE->localize->now;
    		$date_indicator 	= '';
    		
    		if($entry_date > $now && $entry_date != 0)
    		{
    			$date_indicator	.= '<em class="status_indicator" title="'.lang('future_dated').''.$status_text.'" style="background-color: #FF7E3D">['.lang('future_dated').']</em>';
    		}
    		
    		if($expiration_date < $now && $expiration_date != 0)
    		{
    			$date_indicator	.= '<em class="status_indicator" title="'.lang('expired').''.$status_text.'" style="background-color: #990000">['.lang('expired').']</em>';
    		}

    		$ind = str_repeat('	', $level+1);

    		// build the edit entry link
    		$edit_entry_link = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$nodes[$node_id]['channel_id'].AMP.'entry_id='.$nodes[$node_id]['entry_id'];
    		
    		// build the delete node/branch link (if its a branch, we delete all children too)
    		$delete_class = 'node';
    		$delete_link  = $this->base_url.AMP.'method=delete_node'.AMP.'node_id='.$node_id.AMP.'tree_id='.$tree_id;

    		if(isset($node['children']))
    		{
    			$delete_link .= AMP.'type=branch';
    			$delete_class = 'branch';
    		}
    	
			$str .= ($node['level'] != 0) ? $ind."<li id='list_$node_id'>\n" : '';

			$str .= $ind."	<div class='item-wrapper'>\n";

			if($node['level'] != 0)
			{
				$str .= $ind."	<div class='item-handle'></div>\n";
			} 
			
			
        	$str .= $ind."		$status $date_indicator<a href='$this->base_url&amp;method=manage_node&amp;node_id=$node_id&amp;tree_id=$tree_id' class='item-label'>$label</a>\n"; 
        	$str .= $ind."		<div class='item-options'> \n";
        	
        	// show the node_id for superadmins
        	if($this->EE->session->userdata['group_id'] == 1)
        	{
        		$str .= $ind."			<span class='node-info' title='<em>Node ID:</em> <strong>$node_id</strong>";
        		$str .= ($nodes[$node_id]['entry_id']) ? " &nbsp; &nbsp; <em>Node Entry ID:</em> <strong>".$nodes[$node_id]['entry_id']."</strong>" : '';
        		$str .= "'>Info</span>";
        	}
        	
        	// do we have an entry_id
			if($nodes[$node_id]['entry_id'])
			{
				$str .= $ind."  		<a href='$edit_entry_link'>Edit Entry</a> \n";
			}
			

			$str .= $ind."<a href='{$nodes[$node_id]['url']}' target='_blank' title='Visit: {$nodes[$node_id]['url']}'>Visit Page</a> \n";
			$str .= $ind."<a href='$this->base_url&amp;method=manage_node&amp;node_id=$node_id&amp;tree_id=$tree_id'>Edit Node</a> \n";
			
			if( $node['level'] != 0 )
			{
				$str .= $ind."<a href='$delete_link' class='delete_$delete_class'>x</a> \n";
			}
			
			$str .= $ind."</div> \n";
        	$str .= $ind."</div> \n\n";
        	
        	if($node['level'] == 0)
			{
				$str .= "</h3>";
			}
        	
        	if($children)
        	{
        		// recurse!
	            $str .= $this->_build_cp_list($nodes, $children, $ind);
	        }

        	$str .= ($node['level'] != 0) ? "</li>" : '';
        	
        }    

        $str .= ($node['level'] != 0) ? "</ol>" : '';

   	 	return $str;
    }


	// ----------------------------------------------------------------

	/**
	 * CP Content Wrapper
	 *
	 * @return 	void
	 */
	private function _content_wrapper( $content_view, $lang_key, $vars = array() )
	{

		$nav = array(
			'tx_module_home' => $this->base_url,
			'tx_add_tree' => $this->base_url.AMP.'method=edit_tree'.AMP.'new=1'
		);

		$this->EE->cp->set_right_nav($nav);
		
		$vars['content_view'] 	= $content_view;
		$vars['base_url'] 		= $this->base_url;
		$vars['form_base_url']  = $this->form_base_url;
		$vars['theme_base_url'] = $this->theme_base_url;
		$vars['include_title'] = FALSE;

		$cp_page_title = lang($lang_key);

		$this->EE->cp->set_variable( 'cp_page_title', $cp_page_title );
		$this->EE->cp->set_breadcrumb( $this->base_url, TAXONOMY_NAME );

		if( $this->is_ajax_request() )
		{
			$vars['include_title'] = TRUE;
			$this->EE->output->send_ajax_response( $this->EE->load->view( $content_view, $vars, TRUE ) );
		}
		else
		{
			return $this->EE->load->view( '_wrapper', $vars, TRUE );
		}
	}


	
}
/* End of file mcp.taxonomy.php */
/* Location: /system/expressionengine/third_party/taxonomy/mcp.taxonomy.php */