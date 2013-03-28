<?php
	
	echo form_open($form_base_url.AMP.'method=update_tree');

	$this->table->set_template($cp_table_template);

	$this->table->set_heading(
		array('data' => lang('tx_option'), 'style' => 'width:250px'),
		array('data' => lang('tx_value'), 'style' => '')
	);

	$this->table->add_row(
		form_hidden('tree[id]', $tree['id']).
		'<em class="required">* </em>'.form_label(lang('tx_tree_label'), 'tree_label'),
		form_input('tree[label]', set_value('label', $tree['label']), 'id="tree_label"')								
	);

	$this->table->add_row(
		form_hidden('id', $tree['id']).
		form_label(lang('tx_tree_name'), 'tree_name'),
		form_input('tree[name]', set_value('label', $tree['name']), 'id="tree_name"')								
	);
	
	$this->table->add_row(
		form_label(lang('tx_template_preferences'), 'template_preferences'),
		form_multiselect('tree[templates][]', $tree_options['templates']['by_group'], $tree['templates'], 'id="template_preferences"')
	);
	
	$this->table->add_row(
		form_label( lang('tx_taxonomy_channel_preferences'), 'channel_preferences' ),
		form_multiselect('tree[channels][]', $tree_options['channels'], $tree['channels'], 'id="channel_preferences"')
	);
	
	$this->table->add_row(
		form_label(lang('tx_maximum_tree_depth'), 'max_depth'),
		form_input('tree[max_depth]', set_value('label', $tree['max_depth'] ), 'style="width:40px;" maxlength="3" id="max_depth"' )		
	);
	
	if( count($tree_options['allowed_member_groups']) )
	{
		$this->table->add_row(
			form_label( lang('tx_member_preferences'), 'allowed_member_groups' ),
			form_multiselect('tree[member_groups][]', $tree_options['allowed_member_groups'], $tree['permissions'], 'id="allowed_member_groups"')	
		);
	}
	else
	{
		$this->table->add_row(
			lang('tx_member_preferences'),
			lang('tx_no_members_have_module_access')	
		);
	}

	echo $this->table->generate();
	$this->table->clear();
?>

<input type="submit" name="update" class="submit" value="<?=lang('tx_save')?>" />
<input type="submit" name="update_and_return" class="submit" value="<?=lang('tx_save_and_close')?>" />

<?php echo form_close(); ?>