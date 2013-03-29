<?php
	
	echo form_hidden($settings['field_name'].'[tree_id]', $tree['id']);
	echo form_hidden($settings['field_name'].'[node_id]', $data['node_id']);
	echo form_hidden($settings['field_name'].'[custom_url]', $data['custom_url']);

	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
			array('data' => lang('option'), 'class' => 'taxonomy-breadcrumbs'),
			array('data' => lang('value'), 'class' => 'taxonomy-breadcrumbs')
		);

	$this->table->add_row(
		lang('node_label'),
		form_input($settings['field_name'].'[label]', $data['label'])
	);

	// prevent the current node from being selected as a parent
	$parent_select = form_dropdown($settings['field_name'].'[parent_lft]', $nodes, $data['parent_lft']);
	$parent_select = str_replace('value="'.$data['lft'].'"', 'value="'.$data['lft'].'" disabled="disabled"', $parent_select);

	if($data['lft'] == 1)
	{
		echo form_hidden($settings['field_name'].'[parent_lft]', $data['parent_lft']);
	}
	else
	{
		$this->table->add_row(
			lang('select_parent'),
			$parent_select
		);
	}

	if($hide_template)
	{
		echo form_hidden($settings['field_name'].'[template_path]', $data['template_path']);
	}
	elseif(count($templates) && $hide_template === FALSE && $data['custom_url'] == '')
	{
		$this->table->add_row(
			lang('select_template'),
			form_dropdown($settings['field_name'].'[template_path]', $templates, $data['template_path'])
		);
	}

	echo $this->table->generate();

?>
<?php // echo "<pre>"; print_r($data); echo "</pre>"; ?>
<?php // echo "<pre>"; print_r($templates); echo "</pre>"; ?>
<?php // echo "<pre>"; print_r($tree); echo "</pre>"; ?>
<?php // echo "<pre>"; print_r($settings); echo "</pre>"; ?>
