<script type="text/javascript">
	$(document).ready(function(){
		
		$('.tx_template_selector select').hide();

		$('.tx_tree_selector select').each(function() {
			var table = $(this).parents('table:first');
			var channel_id = $(this).data('channel_id');
			table.find('.tx_template_selector select').hide();
			table.find('select[name="options\[default_template\]\['+channel_id+'\]\['+this.value+'\]"]').show();
		});

		$('.tx_tree_selector select').change(function() {
			var table = $(this).parents('table:first');
			var channel_id = $(this).data('channel_id');
			table.find('.tx_template_selector select').hide();
			table.find('select[name="options\[default_template\]\['+channel_id+'\]\['+this.value+'\]"]').show();
		});

	});

</script>


<?php

// we give the taxonomy fieldtype individual settings per channel
// because field groups can be shared across channels.
foreach( $channels as $channel)
{

	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
		array('data' => lang('tx_when_publishing_to').$channel['channel_title'], 'colspan' => '2')
	);

	// get selected state for channel -> tree association
	$selected_channel = '';
	if(isset($settings['channels'][ $channel['channel_id'] ]))
	{
		$selected_channel = $settings['channels'][ $channel['channel_id'] ];
	}

	$this->table->add_row(
		array('data' => lang('tx_select_tree').':', 'style' => 'width:40%'),
		array('data' => form_dropdown(
				"options[channels][{$channel['channel_id']}]", 
				$tree_options, 
				$selected_channel,
				"data-channel_id='{$channel['channel_id']}'"
				), 'class' => 'tx_tree_selector'
		)
	);

	// get selected state for show template picker dropdown.
	$show_templates = 0;
	if(isset($settings['show_templates'][ $channel['channel_id'] ]) && $settings['show_templates'][ $channel['channel_id'] ] == 1)
	{
		$show_templates = 1;
	}

	$this->table->add_row(
		lang('tx_show_template_picker').':',
		form_dropdown("options[show_templates][{$channel['channel_id']}]", $yes_no_options, $show_templates)
	);

	// get selected state for default template.
	$default_template = '';
	if(isset($settings['default_template'][ $channel['channel_id'] ]))
	{
		$default_template = $settings['default_template'][ $channel['channel_id'] ];
	}

	$template_dropdowns = '';

	foreach($tree_options as $tree_id => $tree_label)
	{
		if($tree_id)
		{
			$template_dropdowns .= form_dropdown(
				"options[default_template][{$channel['channel_id']}][{$tree_id}]", 
				$template_options[$tree_id], 
				$default_template
			);
		}
	}

	$this->table->add_row(
		lang('tx_default_template').':',
		array('data' => $template_dropdowns, 'class' => 'tx_template_selector')
	);

	echo $this->table->generate();
	$this->table->clear();
}

?>