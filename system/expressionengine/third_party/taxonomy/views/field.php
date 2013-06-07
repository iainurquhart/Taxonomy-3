<?php
	
	echo form_hidden($settings['field_name'].'[tree_id]', $tree['id']);
	echo form_hidden($settings['field_name'].'[node_id]', $data['node_id']);
	echo form_hidden($settings['field_name'].'[custom_url]', $data['custom_url']);

	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
			array('data' => lang('tx_option'), 'class' => 'taxonomy-breadcrumbs'),
			array('data' => lang('tx_value'), 'class' => 'taxonomy-breadcrumbs')
		);

	$this->table->add_row(
		lang('tx_node_label'),
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
			lang('tx_select_parent'),
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

	foreach($tree['fields'] as $taxonomy_field)
	{
		$value = (isset($data['field_data'][ $taxonomy_field['name'] ])) ? $data['field_data'][ $taxonomy_field['name'] ] : '';
		if((isset($taxonomy_field['show_on_publish']) && $taxonomy_field['show_on_publish'] == 1))
		{
			if($taxonomy_field['type'] == 'text')
			{
				$this->table->add_row(
					$taxonomy_field['label'],
					form_input($settings['field_name'].'[field_data]['.$taxonomy_field['name'].']', $value)
				);
			}
			elseif($taxonomy_field['type'] == 'textarea')
			{
				$this->table->add_row(
					$taxonomy_field['label'],
					form_textarea($settings['field_name'].'[field_data]['.$taxonomy_field['name'].']', $value)
				);
			}
			elseif($taxonomy_field['type'] == 'checkbox')
			{
				$this->table->add_row(
					$taxonomy_field['label'],
					form_checkbox($settings['field_name'].'[field_data]['.$taxonomy_field['name'].']', 1, $value )
				);
			}
		}
		else
		{
			echo form_hidden($settings['field_name'].'[field_data]['.$taxonomy_field['name'].']', $value);
		}

		
	}

	echo $this->table->generate();

?>
<?php // echo "<pre>"; print_r($data); echo "</pre>"; ?>
<?php // echo "<pre>"; print_r($templates); echo "</pre>"; ?>
<?php // echo "<pre>"; print_r($tree); echo "</pre>"; ?>
<?php // echo "<pre>"; print_r($settings); echo "</pre>"; ?>
