<script type="text/javascript">
$(document).ready(function () {

	$(".chzn-select").chosen({max_selected_options: 5});
	// yuck.
	$('.chzn-single').css('width','490px');
	$('.chzn-drop').css('width','498px');
	$('.chzn-search input').css('width','463px');

	function updateUI( $item ){

		var currentId = $( $item ).attr('id');
		var targetId = '#rel-'+currentId;
		var slideDuration = 500;

		console.log('Targetting: ' + targetId);

		if ( $( $item ).is(":checked") )
		{
			$('li'+targetId).stop(true, true).fadeIn({ duration: slideDuration, queue: false }).css('display', 'none').slideDown(slideDuration); 
		}
		else
		{
			$('li'+targetId).stop(true, true).fadeOut({ duration: slideDuration, queue: false }).slideUp(slideDuration);
		}
	}

	$('#taxonomy-subnav input').change(function () {
		updateUI( $(this) );
	 });

});
</script>

<?php // if($root_insert) echo lang('root_node_notice'); ?>
<?php echo form_open($form_base_url.AMP.'method=update_node'); ?>
<div id="taxonomy-node-builder">
	
	<?php // echo '<pre>'; print_r($this_node); echo '</pre>'; ?>
	

	<div id="taxonomy-node-label-holder">
			<div class="taxonomy-inset">
				<label for="node-label">Label / Title:</label>
				<input name="node[label]" value="<?=form_prep($this_node['label'])?>" type="text" id="node-label" />
			</div>
	</div>

	<div id="taxonomy-builder">
		<ul>
			<?php if( ! $root_insert && $node_id == '') : ?>
				<li id="select-parent">
					<div class="taxonomy-inset">
						<label for="node-entry">Select Parent:</label>
						<select id="node-entry" name="node[parent_lft]">
							<?php foreach( $nodes as $node) : ?>
							<option value="<?=$node['lft']?>"><?php echo str_repeat('-&nbsp;', $node['level']).$node['label']?></option>
							<?php endforeach ?>
						</select>
					</div>
				</li>
			<?php endif ?>
			<?php if( count($template_options) ) : ?>
				<li <?php if( ! in_array('template', $this_node['type'])):?> class="taxonomy-hidden"<?php endif ?> id="rel-check-1">
					<div class="taxonomy-inset">
						<label for="node-template">Select Template:</label>
						<select id="node-template" name="node[template_path]">
							<?php if( count($template_options) > 1 ) : ?>
							 <option value="">--</option>
							<?php endif ?>
							<?php foreach( $template_options as $template_id => $template) : ?>
							<option value="<?php echo $template_id; ?>"
								<?php if($template_id == $this_node['template_path']): ?>
								selected="selected"
							<?php endif ?>>
							<?php echo $template; ?></option>
							<?php endforeach ?>
						</select>
					</div>
				</li>
			<?php endif ?>
			<?php if( count($channel_entries) ) : ?>
				<li <?php if( ! in_array('entry', $this_node['type']) && $node_id != ''):?> class="taxonomy-hidden"<?php endif ?> id="rel-check-2">
					<div class="taxonomy-inset">
						<label for="node-entry">Select Entry:</label>
						<select id="node-entry" name="node[entry_id]" class="chzn-select">
							<option value="">--</option>
							<?php foreach( $channel_entries as $entry_id => $entry) : ?>
							<option value="<?php echo $entry_id; ?>"
								<?php if($this_node['entry_id'] == $entry_id): ?>
									selected="selected"
								<?php endif ?>
								<?php if($this_node['entry_id'] != $entry_id && isset($already_selected_entries[$entry_id])): ?>
									disabled="disabled"
								<?php endif ?>
								>
								<?php echo $entry; ?>
							</option>
							<?php endforeach ?>
						</select>
					</div>
				</li>
			<?php endif ?>
			<li <?php if( ! in_array('custom', $this_node['type'])):?> class="taxonomy-hidden"<?php endif ?> id="rel-check-3">
				<div class="taxonomy-inset">
					<label for="node-url">Custom Link:</label>
					<input name="node[custom_url]" type="text" id="node-url" value="<?php echo $this_node['custom_url'] ?>" />
				</div>
			</li>
			<?php if(is_array($tree['fields']) && count($tree['fields'])): ?>
				<li>
					<div class="taxonomy-inset">
						<label>Additional Fields / Attributes:</label>
						<div class="taxonomy-custom-fields">
							<ul>
						<?php foreach($tree['fields'] as $field):?>
							<li class="node-field-<?=$field['type']?>">
							<label for="cf-<?=$field['name']?>"><?=$field['label']?></label>
							<?php

								$value = (isset($this_node['field_data'][ $field['name'] ]))
												? $this_node['field_data'][ $field['name'] ] : '';

								switch($field['type'])
								{
									case 'text':
										echo form_input('node[field_data]['.$field['name'].']', $value, 'id="cf-'.$field['name'].'"');
										break;
									case 'textarea':
										echo form_textarea('node[field_data]['.$field['name'].']', $value, 'id="cf-'.$field['name'].'"');
										break;
									case 'checkbox':
										echo form_checkbox('node[field_data]['.$field['name'].']', 1, $value, 'id="cf-'.$field['name'].'"');
										break;
								}
							?>
							</li>
						<?php endforeach ?>

						</ul>
						</div>
					</div>
				</li>
			<?php endif ?>
		</ul>
	</div>

	<div id="taxonomy-subnav">
		<ul>
			<li><h3>Link Type:</h3></li>
			<?php if( count($template_options) ) : ?>
			<li class="first">
				<label for="check-1">
					<input value="template" type="checkbox" id="check-1" name="node[type][template]"<?php if(in_array('template', $this_node['type'])):?> checked="checked"<?php endif ?>/>
					Template
				</label>
			</li>
			<?php endif ?>
			<?php if( count($channel_entries) ) : ?>
			<li>
				<label for="check-2">
					<input value="entry" type="checkbox" id="check-2" name="node[type][entry]"<?php if(in_array('entry', $this_node['type']) || $node_id == ''):?> checked="checked"<?php endif ?>/> 
					Entry / Page</label>
			</li>
			<?php endif ?>
			<li>
				<label for="check-3">
					<input value="custom" type="checkbox" id="check-3" name="node[type][custom]"<?php if(in_array('custom', $this_node['type'])):?> checked="checked"<?php endif ?>/>
					Custom Link
				</label>
			</li>
		</ul>

		<input type="submit" name="submit" value="Submit" class="taxonomy-submit submit"  />

	</div>

</div>
<?php	
	echo form_hidden('node[node_id]', $node_id);
	echo form_hidden('node[tree_id]', $tree['id']);
	echo form_hidden('node[is_root]', $root_insert); 
	echo form_close(); 
?>