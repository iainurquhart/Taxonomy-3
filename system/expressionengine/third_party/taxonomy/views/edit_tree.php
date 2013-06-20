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
			form_multiselect('tree[member_groups][]', $tree_options['allowed_member_groups'], $tree['member_groups'], 'id="allowed_member_groups"')	
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






<script type='text/javascript'>
	
	// Return a helper with preserved width of cells
	var fixHelper = function(e, ui) {
	    ui.children().each(function() {
	        $(this).width($(this).width());
	    });
	    return ui;
	};
	
	$(document).ready(function() {
		
		var $container = $(".roland_table tbody").roland();
		var opts = $.extend({}, $.fn.roland.defaults);
		
		$(".roland_table tbody").sortable({
			helper: fixHelper, // fix widths
			handle: '.roland_drag_handle',
			cursor: 'move',
			update: function(event, ui) { 
				$.fn.roland.updateIndexes($container, opts); 
			}
		});

	});
</script>

<div class="taxonomy-advanced-settings">
<h3><?=lang('tx_advanced_settings')?></h3>
	<p><?=lang('tx_advanced_settings_instructions')?></p>
	

<?php

	$this->table->set_template($roland_template);
	$this->table->set_heading(
		array('data' => '', 'style' => 'width: 10px;'),
		array('data' => lang('tx_custom_field_label'), 'style' => 'width:30%'),
		array('data' => lang('tx_custom_field_short'), 'style' => 'width:30%'),
		array('data' => lang('tx_type'), 'style' => ''),
		array('data' => lang('tx_display_on_publish'), 'style' => ''),
		array('data' => '', 'style' => 'width: 47px;')
	);
							
	$field_options = array('text'  => 'Text Input', 'textarea'  => 'Textarea',  'checkbox'  => 'Checkbox',);	

	

	if(count($tree['fields']) > 0 && is_array($tree['fields']))
	{	
		// print_r($tree_info['extra']);
		$i = 1;
		foreach($tree['fields'] as $key => $field_row)
		{
	
			$label 	= (isset($field_row['label'])) ? $field_row['label'] : '';
			$name 	= (isset($field_row['name'])) ? $field_row['name'] : '';
			$type 	= (isset($field_row['type'])) ? $field_row['type'] : '';
			$show_on_publish = (isset($field_row['show_on_publish'])) ? $field_row['show_on_publish'] : FALSE;
			
			$this->table->add_row(
				array('data' => $drag_handle, 'class' => 'roland_drag_handle'),
				array('data' => form_input('fields['.$i.'][label]', $label, 'class="taxonomy-field-input"'), 'class' => 'foo'),
				form_input('fields['.$i.'][name]', $name, 'class="taxonomy-field-input"'),
				form_dropdown('fields['.$i.'][type]', $field_options, $type),
				form_checkbox('fields['.$i.'][show_on_publish]', '1', $show_on_publish),
				array('data' => $nav, 'class' => 'roland_nav')
			);
			
			$i++;
			
		}
		
	}
	else
	{
		$this->table->add_row(
				array('data' => $drag_handle, 'class' => 'roland_drag_handle'),
				array('data' => form_input('fields[0][label]', '', 'class="taxonomy-field-input"'), 'class' => 'foo'),
				form_input('fields[0][name]', '', 'class="taxonomy-field-input"'),
				form_dropdown('fields[0][type]', $field_options, ''),
				form_checkbox('fields[0][show_on_publish]', '1', ''),
				array('data' => $nav, 'class' => 'roland_nav')
			);
	}

	
	
			
	
	echo $this->table->generate();
	$this->table->clear(); // needed to reset the table


?>
<p><?=lang('tx_field_notice')?></p>
</div>




<input type="submit" name="update" class="submit" value="<?=lang('tx_save')?>" />
<input type="submit" name="update_and_return" class="submit" value="<?=lang('tx_save_and_close')?>" />
<?php echo form_close(); ?>