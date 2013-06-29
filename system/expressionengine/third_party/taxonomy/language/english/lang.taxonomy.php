<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$this->EE =& get_instance();
$config =& $this->EE->config;

// optional language vars set in config
$taxonomy_tree_label 	 = ($config->item('taxonomy_tree_label')) ? $config->item('taxonomy_tree_label') : 'Tree';
$taxonomy_trees_label 	 = ($config->item('taxonomy_trees_label')) ? $config->item('taxonomy_trees_label') : 'Trees';
$taxonomy_node_label 	 = ($config->item('taxonomy_node_label')) ? $config->item('taxonomy_node_label') : 'node';
$taxonomy_nodes_label 	 = ($config->item('taxonomy_nodes_label')) ? $config->item('taxonomy_nodes_label') : 'nodes';
$taxonomy_add_node_label = ($config->item('taxonomy_add_node_label')) ? $config->item('taxonomy_add_node_label') : 'Add a Node';
$taxonomy_nav_label 	 = ($config->item('taxonomy_nav_label')) ? $config->item('taxonomy_nav_label') : 'Taxonomy';

/* 
	the above vars can be overridden by the user in  EE/system/config/config.php via:
	$config['taxonomy_label'] 		= 'Menus';
	$config['taxonomy_tree_label'] 	= 'Menu';
	$config['taxonomy_trees_label'] = 'Menus';
	$config['taxonomy_add_node_label'] 	= 'Add a menu item';
	$config['taxonomy_node_label'] 	= 'Item';
	$config['taxonomy_nodes_label'] = 'Items';
*/

$lang = array(	
	'taxonomy_module_name' => 'Taxonomy',
	'taxonomy_module_description' => 'Brings hierarchy to channel entries',
	'tx_module_home' => 'Taxonomy Home',
	'tx_manage_trees' => "Manage $taxonomy_trees_label",
	'tx_howdy_stranger' => "Howdy Stranger!",
	'tx_new_install' => "Looks like we've got a brand new install of Taxonomy here for you&hellip;",
	'tx_create_first_tree' => "Create Your First $taxonomy_tree_label",
	'tx_welcome' => "Welcome!",
	'tx_invalid_tree' => "That $taxonomy_tree_label doesn't exist",
	'tx_no_templates_exist' => "No templates exist yet! You need some of those!",
	'tx_tree_id' => "ID",
	'tx_tree_label' => "$taxonomy_tree_label Label",
	'tx_tree_name' => "$taxonomy_tree_label Short Name <br />(Single word, no spaces)",
	'tx_tree_short_name' => "Short Name",
	'tx_tree_preferences' => "Manage $taxonomy_tree_label Settings",
	'tx_delete' => "Delete",
	'tx_edit_tree_settings' => "Edit Settings",
	'tx_option' => "Option",
	'tx_value' => "Value",
	'tx_template_preferences' => "Select Templates associated with this $taxonomy_tree_label",
	'tx_taxonomy_channel_preferences' => "Select Channels associated with this $taxonomy_tree_label",
	'tx_edit_tree' => "Edit $taxonomy_tree_label",
	'tx_maximum_tree_depth' => 'Maximum nesting levels <br /> <i>(Leave 0 for unlimited)</i>',
	'tx_member_preferences' => "Member groups that can access this $taxonomy_tree_label", 
	'tx_no_members_have_module_access' => "<strong>No member groups have access to this module yet.</strong> 
		<br />Groups who have access to the Taxonomy module will appear here.",
	'tx_add_root_node' => "Add a new root $taxonomy_node_label:",
	'tx_manage_node' => "Manage $taxonomy_node_label",
	'tx_add_node' => "Add a new $taxonomy_node_label",
	'tx_edit_nodes' => "Manage $taxonomy_nodes_label",
	'tx_add_tree' => "Add a $taxonomy_tree_label",
	'tx_save' => "Update",
	'tx_save_and_close' => "Update and Finished",
	'tx_tree_updated' => "$taxonomy_tree_label Updated",
	'tx_node_added' => "New $taxonomy_node_label added",
	'tx_node_updated' => "The $taxonomy_node_label has been updated",
	'tx_select_tree' => "Associate entries to the following $taxonomy_tree_label",
	'tx_show_template_picker' => "Show Template Select",
	'tx_default_template' => "Default Template",
	'tx_when_publishing_to' => "When publishing to: ",
	'tx_field_not_configured' => "This Taxonomy field is not configured for publishing to this Channel",
	'tx_tree_requires_a_root' => "The configured $taxonomy_tree_label requires a root $taxonomy_node_label before entries can be associated",
	'tx_advanced_settings' => "Advanced Settings: $taxonomy_tree_label Custom Fields",
	'tx_advanced_settings_instructions' => "Custom fields are optional, and will appear to publishers when editing $taxonomy_nodes_label via the module interface.<br />By selecting 'Display on publish?' the field will appear on the Taxonomy Fieldtype too.",
	'tx_custom_field_label'			=> 'Field label <br /><small>(Visible to publishers)</small>',
	'tx_custom_field_short'			=> 'Field short name <br /><small>(Single word, no spaces. Underscores and dashes allowed)</small>',
	'tx_type'							=> 'Type',
	"tx_display_on_publish"			=> 'Display on publish?',
	"tx_field_notice"					=> 'Please note: Changing a \'Field short name \' will not update already existing values if they have been entered.',
	'tx_node_label' => "Label / Title:",
	'tx_select_parent' => "Select Parent:",
	"nav_taxonomy_nav_label"		=> "$taxonomy_nav_label",
	"tx_select_template" => 'Select Template:',
	'tx_fetch_title' => 'Fetch the Entry Title'
);

/* End of file lang.taxonomy.php */
/* Location: /system/expressionengine/third_party/taxonomy/language/english/lang.taxonomy.php */
