<script type="text/javascript">
	$(document).ready(function(){

		$(".delete_branch, .delete_node").click(function(e) { 
			var answer = confirm("Are you sure you want to delete?")
			if (!answer){
				e.preventDefault();
			}
		});
		
		// fix for stoopid cursor bug
		// http://forum.jquery.com/topic/chrome-text-select-cursor-on-drag
		this.onselectstart = function () { return false; };

		
		$('span.node-info').tipsy({fade: false, gravity: 'e', html: true});
		$('em.status_indicator').tipsy({fade: false, gravity: 's', html: true});
		
		$('ol#taxonomy-list').nestedSortable(
		{	
			disableNesting: 'no-nest',
			forcePlaceholderSize: true,
			handle: 'div.item-handle',
			items: 'li',
			opacity: .92,
			placeholder: 'placeholder',
			tabSize: 25,
			tolerance: 'pointer',
			toleranceElement: '> div',
			maxLevels: <?=$tree['max_depth']?>
			
		});
		
		// fix the tree height to prevent any 'jumping'
		$('ol#taxonomy-list').height($('ol#taxonomy-list').height());

		$( "ol#taxonomy-list" ).bind( "sortupdate", function(event, ui) 
		{
		
			$('ol#taxonomy-list').addClass('taxonomy_update_underway');
		
			serialized = $('ol#taxonomy-list').nestedSortable('toArray', {startDepthCount: 1});

			// console.log( JSON.stringify(serialized) );

			serialized_string = JSON.stringify(serialized);
			
			// prep our vars for posting
			var $form 				= $('#save-taxonomy form'),
				p_XID 				= $form.find( 'input[name="XID"]' ).val(),
		        p_tree_id 			= $form.find( 'input[name="tree_id"]' ).val(),
		        p_taxonomy_order 	= serialized_string,
		        p_last_updated 		= $form.find( 'input[name="last_updated"]' ).val(),
		        url					= $form.attr( 'action' );
	
		    	// Send the data using post
		    	$.post( url, { 'XID': p_XID, 'tree_id': p_tree_id, 'taxonomy_order': p_taxonomy_order, 'last_updated': p_last_updated},
		      		function( data ) 
		      		{
						
			      		var msg = data.data;
			      		// console.log(data.last_updated);
			      		// flag if there's a date mismatch
			      		if(msg == 'last_update_mismatch'){
	                    	$('#taxonomy-list-container').html('<div class="taxonomy-error"><h3>Error: The tree you are sorting is out of date.<br />(Another user editing the tree right now?)</h3><p> Your changes have not been saved to prevent possible damage to the Taxonomy Tree. <br />Please refresh the page to get the latest version.</p></div>');
	                    }
	
						// update the timestamp field with response timestamp
			      		$("#save-taxonomy .last-updated").val(data.last_updated);
			      		
			          	// $( "#taxonomy-output" ).html( msg );
			          	
			          	// remove the updator indicator
			          	$('ol#taxonomy-list').removeClass('taxonomy_update_underway');
			          	
			          	$.ee_notice("Tree order updated", {type: 'success'});
		          	
		     		}, "json");

		});


		/*
		$('a.add-node').click( function(a){

				var url = $(this).attr('href');

				$.get(url, function(data){

					data = jQuery.parseJSON(data);
				    // console.log(jQuery.parseJSON(data));
				   	$('#taxonomy-insert-node-controls').html(data).slideDown();
				});

				a.preventDefault();

		});
		*/


	});
</script>

<div class="cp_button taxonomy_add_node">
	<a href="<?=$base_url?>&amp;method=manage_node&amp;tree_id=<?=$tree['id']?>" class="add-node"><?=lang('tx_add_node')?></a>
</div>

<h2 id="taxonomy-tree-name"><?=$tree['label']?></h2>

<div class="clear"></div>
<div id="taxonomy-insert-node-controls" style="display:none;"></div>

<div id="taxonomy-list-container">
	<?php echo $cp_list ?>
</div>

<div id="save-taxonomy">
		<?php echo form_open($form_base_url.AMP.'method=reorder_nodes'.AMP.'tree_id='.$tree['id']); ?>
			<input type="text" name="tree_id" value="<?=$tree['id']?>" />
			<input type="text" value="<?=$tree['last_updated']?>" class="input last-updated" name="last_updated" />
			<!-- 			<textarea class="taxonomy-serialise" name="taxonomy_order" style="width: 100%; height: 300px;"></textarea>

<input type="submit" value="submit"> -->
		<?=form_close()?>
	</div>


<?php // debug_array( $tree ); ?>
