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
		array('data' => lang('tx_node_label').' <span class="taxonomy_fetch_title" title="'.lang('tx_fetch_title').'">+</span>',
			 'style' => 'width: 200px'),
		form_input($settings['field_name'].'[label]', $data['label'], 'class="taxonomy_label" style="width: 60%;"')
	);

	if($data['lft'] == 1)
	{
		echo form_hidden($settings['field_name'].'[parent_lft]', $data['parent_lft']);
	}
	else
	{

		$parent_select = '<select name="'.$settings['field_name'].'[parent_lft]">';
		foreach($nodes as $node)
		{
			$attributes = '';
			$prefix = ($node['level'] >= 2) ? str_repeat('-&nbsp;', $node['level']) : '';
			// don't allow nesting level beyond tree settings,
			// & don't allow node to select itself as parent.
			if(($tree['max_depth'] && $node['level'] >= $tree['max_depth']) || ($data['lft'] == $node['lft']))
			{
				$attributes .= ' disabled="disabled"';
			}
			if($data['parent_lft'] == $node['lft'])
			{
				$attributes .= ' selected="selected"';
			}
			$parent_select .= '<option value="'.$node['lft'].'"'.$attributes.'>'.$prefix.$node['label'].'</option>';
		}
		$parent_select .= '</select>';

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
			lang('tx_select_template'),
			form_dropdown($settings['field_name'].'[template_path]', $templates, $data['template_path'])
		);
	}


	foreach($tree['fields'] as $key => $field)
	{
		if(!empty($field['show_on_publish']))
		{
			$this->table->add_row(
					$field['label'].':',
					$field['html']
				);
		}
		else
		{
			echo "<div style='display: none';>".$field['html']."</div>";
		}
	}

	echo $this->table->generate();

?>